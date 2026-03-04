<?php

namespace App\Jobs;

use App\Mail\EventSignupReminderMail;
use App\Models\EventSignupReminder;
use App\Services\EventSignupReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\Logger;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SendEventSignupReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $reminderId)
    {
        $this->onQueue('mail'); // uses Queueable trait property safely
    }

    /**
     * @throws Throwable
     */
    public function handle(EventSignupReminderService $service): void
    {
        $token = (string) Str::uuid();
        $nowUtc = CarbonImmutable::now('UTC');
        $stale = $nowUtc->subMinutes(15);

        $claimed = EventSignupReminder::whereKey($this->reminderId)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('send_at', '<=', $nowUtc->toDateTimeString())
            ->where(function ($q) use ($stale) {
                $q->whereNull('reserved_at')
                    ->orWhere('reserved_at', '<=', $stale->toDateTimeString());
            })
            ->update([
                'reserved_at' => $nowUtc->toDateTimeString(),
                'reservation_token' => $token,
            ]);

        if ($claimed == 0) {
            return;
        }

        $reminder = EventSignupReminder::with(['signup.subscriber', 'signup.page.event'])
            ->find($this->reminderId);

        if (!$reminder) return;
        if ($reminder->reservation_token !== $token) return;
        if ($reminder->sent_at || $reminder->canceled_at) return;

        $signup = $reminder->signup;
        if (!$signup || $signup->status !== 'active') {
            logger()->info('EventSignupReminderJob: Signup not active, skipping reminder');
            $reminder->update([
                'canceled_at' => now(),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
            return;
        }

        $subscriber = $signup->subscriber;
        $event = $signup->page?->event;
        $title = $signup->page?->title_override ?? $event?->title ?? 'Event';
        $description = $signup->page?->description ?? $event?->description ?? '';

        if (!$subscriber || !$subscriber->email || !$event) {
            logger()->info('EventSignupReminderJob: Subscriber or event not found, skipping reminder');
            $reminder->update([
                'canceled_at' => now(),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
            return;
        }

        try {
            $expires = now()->addDays(30);

            $manageUrl = URL::temporarySignedRoute(
                'public.signup.manage.show',
                $expires,
                ['eventSignup' => $signup] // uses uuid route key if you set it
            );

            $unsubscribeUrl = URL::temporarySignedRoute(
                'public.signup.unsubscribe.show',
                $expires,
                ['eventSignup' => $signup]
            );

            $occurrenceIdUtc = CarbonImmutable::parse($reminder->occurrence_starts_at)->utc();
            $effectiveStartUtc = $service->effectiveStartForOccurrence($event, $occurrenceIdUtc);

            Mail::to($subscriber->email)->send(new EventSignupReminderMail(
                eventTitle: $title,
                eventDescription: $description,
                reminderType: $reminder->reminder_type,
                occurrenceStartUtc: $effectiveStartUtc,
                timezone: $event->timezone ?: config('app.timezone', 'UTC'),
                location: $event->location,
                manageUrl: $manageUrl,
                unsubscribeUrl: $unsubscribeUrl,
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
