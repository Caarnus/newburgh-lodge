<?php

namespace App\Jobs;

use App\Mail\VolunteerSignupReminderMail;
use App\Models\VolunteerSignupReminder;
use App\Services\VolunteerSignupReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendVolunteerSignupReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $reminderId)
    {
        $this->onQueue('mail');
    }

    /**
     * @throws Throwable
     */
    public function handle(VolunteerSignupReminderService $reminderService): void
    {
        $token = (string) Str::uuid();
        $nowUtc = CarbonImmutable::now('UTC');
        $stale = $nowUtc->subMinutes(15);

        $claimed = VolunteerSignupReminder::query()
            ->whereKey($this->reminderId)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('send_at', '<=', $nowUtc->toDateTimeString())
            ->where(function ($query) use ($stale) {
                $query->whereNull('reserved_at')
                    ->orWhere('reserved_at', '<=', $stale->toDateTimeString());
            })
            ->update([
                'reserved_at' => $nowUtc->toDateTimeString(),
                'reservation_token' => $token,
            ]);

        if ($claimed === 0) {
            return;
        }

        $reminder = VolunteerSignupReminder::query()
            ->with([
                'registrant.sheet.event',
                'registrant.assignments.slot.role',
            ])
            ->find($this->reminderId);

        if (!$reminder || $reminder->reservation_token !== $token || $reminder->sent_at || $reminder->canceled_at) {
            return;
        }

        $registrant = $reminder->registrant;
        $sheet = $registrant?->sheet;
        $event = $sheet?->event;

        if (!$registrant || !$registrant->email || !$sheet || !$event) {
            $reminder->update([
                'canceled_at' => now(),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
            return;
        }

        $assignments = $registrant->assignments
            ->where('status', 'active')
            ->sortBy(fn ($assignment) => [
                $assignment->slot?->role?->sort_order ?? 0,
                $assignment->slot?->sort_order ?? 0,
                $assignment->id,
            ])
            ->values()
            ->map(fn ($assignment) => [
                'role_title' => $assignment->slot?->role?->title ?? 'Role',
                'starts_at' => $assignment->slot ? substr((string) $assignment->slot->starts_at, 0, 5) : '',
                'ends_at' => $assignment->slot ? substr((string) $assignment->slot->ends_at, 0, 5) : '',
            ])
            ->all();

        if ($assignments === []) {
            $reminder->update([
                'canceled_at' => now(),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
            return;
        }

        try {
            $occurrenceIdUtc = CarbonImmutable::parse($reminder->occurrence_starts_at)->utc();
            $effectiveStart = $reminderService->effectiveStartForOccurrence($event, $occurrenceIdUtc);

            $signupUrl = $sheet->slug ? route('public.volunteer-signups.show', $sheet->slug) : null;

            Mail::to($registrant->email)->send(new VolunteerSignupReminderMail(
                eventTitle: $sheet->title_override ?: ($event->title ?? 'Event'),
                eventDescription: $sheet->description ?: $event->description,
                reminderType: $reminder->reminder_type,
                occurrenceStartUtc: $effectiveStart,
                timezone: $event->timezone ?: config('app.timezone', 'UTC'),
                location: $event->location,
                assignments: $assignments,
                signupUrl: $signupUrl,
            ));

            $reminder->update([
                'sent_at' => now(),
                'last_error' => null,
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
        } catch (Throwable $e) {
            $reminder->update([
                'last_error' => Str::limit($e->getMessage(), 2000),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);

            throw $e;
        }
    }
}
