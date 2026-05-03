<?php

namespace App\Services;

use App\Models\OrgEvent;
use App\Models\VolunteerSignupRegistrant;
use App\Models\VolunteerSignupReminder;
use App\Models\VolunteerSignupSheet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class VolunteerSignupReminderService
{
    public function __construct(
        private OrgEventRecurrenceService $recurrenceService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function syncForRegistrant(VolunteerSignupRegistrant $registrant, int $windowDays = 90): void
    {
        $registrant->loadMissing([
            'sheet.event',
            'assignments' => fn ($q) => $q->where('status', 'active'),
        ]);

        $sheet = $registrant->sheet;
        $event = $sheet?->event;

        if (!$sheet || !$event) {
            return;
        }

        $nowUtc = CarbonImmutable::now('UTC');
        $windowEndUtc = $nowUtc->addDays($windowDays);

        if ($registrant->assignments->isEmpty()) {
            $this->cancelUnsentReminders($registrant, $nowUtc);
            return;
        }

        $enabledTypes = $this->enabledTypes($sheet);
        if ($enabledTypes === []) {
            $this->cancelUnsentReminders($registrant, $nowUtc);
            return;
        }

        $maxLeadDays = in_array('week', $enabledTypes, true) ? 7 : 1;
        $occurrenceEndUtc = $windowEndUtc->addDays($maxLeadDays);
        $occurrences = $this->occurrencesForEvent($event, $nowUtc, $occurrenceEndUtc);

        $this->syncReminderRows($registrant, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc);
    }

    /**
     * @throws Throwable
     */
    public function syncForSheet(VolunteerSignupSheet $sheet, int $windowDays = 90): void
    {
        $sheet->loadMissing('event');
        $event = $sheet->event;

        if (!$event) {
            return;
        }

        $nowUtc = CarbonImmutable::now('UTC');
        $windowEndUtc = $nowUtc->addDays($windowDays);
        $enabledTypes = $this->enabledTypes($sheet);

        if ($enabledTypes === []) {
            VolunteerSignupReminder::query()
                ->whereHas('registrant', fn ($q) => $q->where('volunteer_signup_sheet_id', $sheet->id))
                ->whereNull('sent_at')
                ->whereNull('canceled_at')
                ->update([
                    'canceled_at' => $nowUtc->toDateTimeString(),
                    'reserved_at' => null,
                    'reservation_token' => null,
                ]);
            return;
        }

        $maxLeadDays = in_array('week', $enabledTypes, true) ? 7 : 1;
        $occurrenceEndUtc = $windowEndUtc->addDays($maxLeadDays);
        $occurrences = $this->occurrencesForEvent($event, $nowUtc, $occurrenceEndUtc);

        $registrants = VolunteerSignupRegistrant::query()
            ->where('volunteer_signup_sheet_id', $sheet->id)
            ->with(['assignments' => fn ($q) => $q->where('status', 'active')])
            ->get();

        foreach ($registrants as $registrant) {
            if ($registrant->assignments->isEmpty()) {
                $this->cancelUnsentReminders($registrant, $nowUtc);
                continue;
            }

            $this->syncReminderRows($registrant, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc);
        }
    }

    public function effectiveStartForOccurrence(OrgEvent $event, CarbonImmutable $occurrenceIdUtc): CarbonImmutable
    {
        $rows = $this->recurrenceService->occurrencesBetween(
            $event,
            $occurrenceIdUtc->subMinute(),
            $occurrenceIdUtc->addMinute(),
            5,
        );

        foreach ($rows as $row) {
            if ($row['occurrence_id_utc']->equalTo($occurrenceIdUtc)) {
                return $row['effective_start_utc'];
            }
        }

        return $occurrenceIdUtc;
    }

    private function enabledTypes(VolunteerSignupSheet $sheet): array
    {
        $types = [];
        if ($sheet->remind_week_before) {
            $types[] = 'week';
        }
        if ($sheet->remind_day_before) {
            $types[] = 'day';
        }

        return $types;
    }

    private function occurrencesForEvent(OrgEvent $event, CarbonImmutable $fromUtc, CarbonImmutable $toUtc): array
    {
        $rows = $this->recurrenceService->occurrencesBetween($event, $fromUtc, $toUtc, 5000);

        return array_map(fn ($row) => [
            'occurrence_id_utc' => $row['occurrence_id_utc'],
            'effective_start_utc' => $row['effective_start_utc'],
        ], $rows);
    }

    /**
     * @throws Throwable
     */
    private function syncReminderRows(
        VolunteerSignupRegistrant $registrant,
        array $enabledTypes,
        array $occurrences,
        CarbonImmutable $nowUtc,
        CarbonImmutable $windowEndUtc
    ): void {
        DB::transaction(function () use ($registrant, $enabledTypes, $occurrences, $nowUtc, $windowEndUtc) {
            VolunteerSignupReminder::query()
                ->where('volunteer_signup_registrant_id', $registrant->id)
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

            foreach ($occurrences as $occurrence) {
                $occurrenceIdUtc = $occurrence['occurrence_id_utc'];
                $effectiveStartUtc = $occurrence['effective_start_utc'];

                foreach ($enabledTypes as $type) {
                    $sendAt = $type === 'week'
                        ? $effectiveStartUtc->subDays(7)
                        : $effectiveStartUtc->subDay();

                    if ($sendAt->lte($nowUtc) || $sendAt->gt($windowEndUtc)) {
                        continue;
                    }

                    $rows[] = [
                        'volunteer_signup_registrant_id' => $registrant->id,
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

            if ($rows !== []) {
                VolunteerSignupReminder::query()->upsert(
                    $rows,
                    ['volunteer_signup_registrant_id', 'reminder_type', 'occurrence_starts_at'],
                    ['send_at', 'canceled_at', 'reserved_at', 'reservation_token', 'updated_at']
                );
            }
        });
    }

    private function cancelUnsentReminders(VolunteerSignupRegistrant $registrant, CarbonImmutable $nowUtc): void
    {
        VolunteerSignupReminder::query()
            ->where('volunteer_signup_registrant_id', $registrant->id)
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->update([
                'canceled_at' => $nowUtc->toDateTimeString(),
                'reserved_at' => null,
                'reservation_token' => null,
            ]);
    }
}
