<?php

namespace App\Services;

use App\Models\EventSignup;
use App\Models\EventSignupReminder;
use App\Models\OrgEvent;
use App\Models\OrgEventOccurrenceOverride;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class EventSignupReminderService
{
    public function __construct(private OrgEventRecurrenceService $recurrence)
    {
    }

    /**
     * @throws Throwable
     */
    public function syncForSignup(EventSignup $signup, int $windowDays = 90): void
    {
        $signup->loadMissing(['page.event', 'subscriber']);
        $event = $signup->page?->event;

        if (!$event) {
            return;
        }

        $nowUtc = CarbonImmutable::now('UTC');
        $windowEndUtc = $nowUtc->addDays($windowDays);

        // If canceled, cancel everything unsent (any time horizon)
        if ($signup->status !== 'active') {
            EventSignupReminder::where('event_signup_id', $signup->id)
                ->whereNull('sent_at')
                ->whereNull('canceled_at')
                ->update([
                    'canceled_at' => $nowUtc->toDateTimeString(),
                    'reserved_at' => null,
                    'reservation_token' => null,
                ]);
            return;
        }

        $enabledTypes = $this->enabledTypes($signup);
        $maxLeadDays = $this->maxLeadDays($enabledTypes);

        // We need to look ahead past the window end to catch week-before reminders
        // whose send_at is inside the window but occurrence is slightly outside.
        $occurrenceEndUtc = $windowEndUtc->addDays($maxLeadDays);

        // Build occurrences (UTC instants; DST-safe) + apply per-occurrence overrides
        $occurrences = $this->occurrencesForEvent($event, $nowUtc, $occurrenceEndUtc);

        // Rolling window applies to send_at.
        // Strategy:
        // 1) cancel unsent reminders in the window (send_at <= windowEnd)
        // 2) upsert the reminders we want (canceled_at null)
        $this->handledDBTransaction($signup, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc);
    }

    /**
     * Bulk variant: compute occurrences once, sync all active signups for this event.
     * Use this when the event changes (rrule/start/timezone/overrides).
     */
    public function syncForEvent(OrgEvent $event, int $windowDays = 90): void
    {
        $event->loadMissing(['signupPage.signups.subscriber']);

        $page = $event->signupPage;
        if (!$page || !$page->is_enabled) {
            return;
        }

        $nowUtc = CarbonImmutable::now('UTC');
        $windowEndUtc = $nowUtc->addDays($windowDays);

        // Determine max lead across all signups (week/day/hour)
        $maxLeadDays = 7;

        $occurrenceEndUtc = $windowEndUtc->addDays($maxLeadDays);
        $occurrences = $this->occurrencesForEvent($event, $nowUtc, $occurrenceEndUtc);

        foreach ($page->signups->where('status', 'active') as $signup) {
            $this->syncForSignupUsingPrecomputed($signup, $occurrences, $nowUtc, $windowEndUtc);
        }
    }

    public function effectiveStartForOccurrence(OrgEvent $event, CarbonImmutable $occurrenceIdUtc): CarbonImmutable
    {
        // If you haven’t added overrides, just return $occurrenceIdUtc.
        $override = OrgEventOccurrenceOverride::where('org_event_id', $event->id)
            ->where('occurrence_starts_at', $occurrenceIdUtc->toDateTimeString())
            ->first();

        if (!$override || $override->is_canceled) {
            return $occurrenceIdUtc;
        }

        return CarbonImmutable::parse($override->override_starts_at)->utc();
    }

    /**
     * @throws Throwable
     */
    private function syncForSignupUsingPrecomputed(
        EventSignup $signup,
        array $occurrences,
        CarbonImmutable $nowUtc,
        CarbonImmutable $windowEndUtc
    ): void {
        $enabledTypes = $this->enabledTypes($signup);

        $this->handledDBTransaction($signup, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc);
    }

    /**
     * DST-safe occurrence expansion.
     * Returns:
     * - occurrence_id_utc: original occurrence start (UTC) used as stable key
     * - effective_start_utc: overridden start (UTC) used to compute reminder times
     */
    private function occurrencesForEvent(OrgEvent $event, CarbonImmutable $fromUtc, CarbonImmutable $toUtc): array
    {
        $rows = $this->recurrence->occurrencesBetween($event, $fromUtc, $toUtc, 5000);

        // Reminders only need the stable id and effective start
        return array_map(fn ($r) => [
            'occurrence_id_utc'     => $r['occurrence_id_utc'],
            'effective_start_utc'   => $r['effective_start_utc'],
        ], $rows);
    }

    private function applyOverride(int $eventId, CarbonImmutable $occurrenceIdUtc, CarbonImmutable $defaultEffective): ?CarbonImmutable
    {
        $override = OrgEventOccurrenceOverride::where('org_event_id', $eventId)
            ->where('occurrence_starts_at', $occurrenceIdUtc->toDateTimeString())
            ->first();

        if (!$override) return $defaultEffective;
        if ($override->is_canceled) return null;

        return CarbonImmutable::parse($override->override_starts_at)->utc();
    }

    private function enabledTypes(EventSignup $signup): array
    {
        $types = [];
        if ($signup->remind_week_before) $types[] = 'week';
        if ($signup->remind_day_before)  $types[] = 'day';
        if ($signup->remind_hour_before) $types[] = 'hour';
        return $types;
    }

    private function maxLeadDays(array $types): int
    {
        // only "week" requires > 1 day lookahead beyond window
        return in_array('week', $types, true) ? 7 : 1;
    }

    private function sendAtForType(string $type, CarbonImmutable $effectiveStartUtc): CarbonImmutable
    {
        return match ($type) {
            'week' => $effectiveStartUtc->subDays(7),
            'day'  => $effectiveStartUtc->subDay(),
            'hour' => $effectiveStartUtc->subHour(),
            default => $effectiveStartUtc->subDay(),
        };
    }

    /**
     * @throws Throwable
     */
    private function handledDBTransaction(EventSignup $signup, array $enabledTypes, array $occurrences, CarbonImmutable $nowUtc, CarbonImmutable $windowEndUtc): void
    {
        DB::transaction(function () use ($signup, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc) {
            EventSignupReminder::where('event_signup_id', $signup->id)
                ->whereNull('sent_at')
                ->whereNull('canceled_at')
                ->where('send_at', '<=', $windowEndUtc->toDateTimeString())
                ->update([
                    'canceled_at' => $nowUtc->toDateTimeString(),
                    'reserved_at' => null,
                    'reservation_token' => null,
                ]);

            $rows = [];
            $nowStr = $nowUtc->toDateTimeString();

            foreach ($occurrences as $occ) {
                $occurrenceIdUtc = $occ['occurrence_id_utc'];     // identifier (original)
                $effectiveStartUtc = $occ['effective_start_utc']; // override applied

                foreach ($enabledTypes as $type) {
                    $sendAt = $this->sendAtForType($type, $effectiveStartUtc);

                    // Only keep reminders whose send time falls within the rolling window.
                    if ($sendAt->lte($nowUtc) || $sendAt->gt($windowEndUtc)) {
                        continue;
                    }

                    $rows[] = [
                        'event_signup_id' => $signup->id,
                        'reminder_type' => $type,
                        'occurrence_starts_at' => $occurrenceIdUtc->toDateTimeString(),
                        'send_at' => $sendAt->toDateTimeString(),
                        'canceled_at' => null,
                        'reserved_at' => null,
                        'reservation_token' => null,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }
            }

            if ($rows) {
                EventSignupReminder::upsert(
                    $rows,
                    ['event_signup_id', 'reminder_type', 'occurrence_starts_at'],
                    ['send_at', 'canceled_at', 'reserved_at', 'reservation_token', 'updated_at']
                );
            }
        });
    }
}
