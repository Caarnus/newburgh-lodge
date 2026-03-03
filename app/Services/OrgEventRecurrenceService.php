<?php

namespace App\Services;

use App\Models\OrgEvent;
use App\Models\OrgEventOccurrenceOverride;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RRule\RRule;

class OrgEventRecurrenceService
{
    private const WEEKDAYS = ['MO','TU','WE','TH','FR','SA','SU'];
    private const FREQS = ['DAILY','WEEKLY','MONTHLY','YEARLY'];

    /**
     * Build an RFC-ish string including DTSTART;TZID=... and RRULE:...
     * Expects $startLocal to be in the event timezone.
     */
    public function buildRRuleFromOptions(array $opts, CarbonImmutable $startLocal, string $tz): ?string
    {
        $mode = $opts['mode'] ?? 'none';
        if ($mode === 'none') return null;

        $body = null;

        if ($mode === 'nth-weekday') {
            $nth = (int)($opts['nth'] ?? 1);
            $weekday = (string)($opts['weekday'] ?? 'TU');
            $interval = max(1, (int)($opts['interval'] ?? 1));

            if (!in_array($weekday, self::WEEKDAYS, true)) $weekday = 'TU';
            if (!in_array($nth, [1,2,3,4,5,-1], true)) $nth = 1;

            $body = "FREQ=MONTHLY;BYDAY={$nth}{$weekday};INTERVAL={$interval}";
        }

        if ($mode === 'interval') {
            $freq = strtoupper((string)($opts['freq'] ?? 'WEEKLY'));
            if (!in_array($freq, self::FREQS, true)) $freq = 'WEEKLY';

            $interval = max(1, (int)($opts['interval'] ?? 1));

            $body = "FREQ={$freq};INTERVAL={$interval}";

            $byweekday = $opts['byweekday'] ?? null;
            if ($freq === 'WEEKLY' && is_array($byweekday) && count($byweekday)) {
                $days = array_values(array_filter(array_map(
                    fn ($d) => strtoupper((string)$d),
                    $byweekday
                ), fn ($d) => in_array($d, self::WEEKDAYS, true)));

                if ($days) {
                    $body .= ';BYDAY=' . implode(',', $days);
                }
            }
        }

        if (!$body) return null;

        $ends = $opts['ends'] ?? 'never';
        if ($ends === 'until') {
            $untilYmd = $opts['until'] ?? null; // YYYY-MM-DD
            if ($untilYmd) {
                $untilUtc = CarbonImmutable::createFromFormat('Y-m-d', $untilYmd, $tz)
                    ->endOfDay()
                    ->utc();
                $body .= ';UNTIL=' . $untilUtc->format('Ymd\THis\Z');
            }
        } elseif ($ends === 'count') {
            $count = (int)($opts['count'] ?? 0);
            if ($count > 0) $body .= ";COUNT={$count}";
        }

        // DTSTART in local wall clock in TZ
        $dtstart = $startLocal->setTimezone($tz)->format('Ymd\THis');

        return "DTSTART;TZID={$tz}:{$dtstart}\nRRULE:{$body}";
    }

    /**
     * Parse stored RRULE string into the UI-friendly repeat_options structure.
     * This is intentionally limited to the modes your UI supports.
     */
    public function parseRepeatOptions(?string $rrule, string $fallbackTz): ?array
    {
        if (!$rrule) return null;

        $tz = $fallbackTz;
        $ruleLine = null;

        foreach (preg_split('/\r?\n/', trim($rrule)) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if (stripos($line, 'DTSTART') === 0) {
                // DTSTART;TZID=America/Chicago:20250819T180000
                if (preg_match('/^DTSTART(?:;TZID=|;TZID)?=?([^:;]+):/i', $line, $m)) {
                    $tz = $m[1] ?: $tz;
                } elseif (preg_match('/^DTSTART;TZID=([^:]+):/i', $line, $m)) {
                    $tz = $m[1] ?: $tz;
                }
            }

            if (stripos($line, 'RRULE') === 0) {
                $ruleLine = $line;
            } elseif (str_contains($line, 'FREQ=')) {
                $ruleLine = 'RRULE:' . ltrim($line, 'Rrule:');
            }
        }

        if (!$ruleLine) return null;

        $ruleLine = preg_replace('/^RRULE:/i', '', $ruleLine);
        $pairs = [];
        foreach (explode(';', $ruleLine) as $chunk) {
            if (!str_contains($chunk, '=')) continue;
            [$k, $v] = explode('=', $chunk, 2);
            $pairs[strtoupper(trim($k))] = trim($v);
        }

        $freq = strtoupper($pairs['FREQ'] ?? 'MONTHLY');
        $interval = (int)($pairs['INTERVAL'] ?? 1);
        $byday = $pairs['BYDAY'] ?? null;
        $bysetpos = isset($pairs['BYSETPOS']) ? (int)$pairs['BYSETPOS'] : null;

        $ends = 'never';
        $until = null;
        $count = null;

        if (!empty($pairs['COUNT'])) {
            $ends = 'count';
            $count = (int)$pairs['COUNT'];
        } elseif (!empty($pairs['UNTIL'])) {
            $ends = 'until';
            // UNTIL is UTC stamp
            $u = $pairs['UNTIL'];
            if (preg_match('/^(\d{8})T(\d{6})Z$/', $u, $m)) {
                $dtUtc = CarbonImmutable::createFromFormat('YmdHis', $m[1] . $m[2], 'UTC');
                $until = $dtUtc->setTimezone($tz)->format('Y-m-d');
            }
        }

        if ($freq === 'MONTHLY' && $byday && preg_match('/^(-?\d+)(MO|TU|WE|TH|FR|SA|SU)$/', strtoupper(trim($byday)), $m)) {
            $nth = (int)$m[1];
            $weekday = $m[2];

            return [
                'mode' => 'nth-weekday',
                'nth' => $nth,
                'weekday' => $weekday,
                'freq' => 'MONTHLY',
                'interval' => max(1, $interval),
                'byweekday' => null,
                'ends' => $ends,
                'until' => $until,
                'count' => $count,
            ];
        }

        // Interval mode
        $byweekday = null;
        if ($freq === 'WEEKLY' && $byday) {
            $days = array_values(array_filter(array_map('trim', explode(',', strtoupper($byday)))));
            $days = array_values(array_filter($days, fn ($d) => in_array($d, self::WEEKDAYS, true)));
            $byweekday = $days ?: null;
        }

        return [
            'mode' => 'interval',
            'nth' => null,
            'weekday' => null,
            'freq' => in_array($freq, self::FREQS, true) ? $freq : 'WEEKLY',
            'interval' => max(1, $interval),
            'byweekday' => $byweekday,
            'ends' => $ends,
            'until' => $until,
            'count' => $count,
        ];
    }

    /**
     * Expand occurrences between two UTC instants and apply overrides.
     * Returns items with:
     * - occurrence_id_utc (stable key)
     * - effective_start_utc
     * - effective_end_utc
     */
    public function occurrencesBetween(OrgEvent $event, CarbonImmutable $fromUtc, CarbonImmutable $toUtc, int $guard = 5000): array
    {
        $durationSeconds = null;
        if ($event->start && $event->end) {
            $durationSeconds = CarbonImmutable::parse($event->end)->diffInSeconds(CarbonImmutable::parse($event->start), false);
            if ($durationSeconds <= 0) $durationSeconds = null;
        }

        $overrides = OrgEventOccurrenceOverride::where('org_event_id', $event->id)
            ->whereBetween('occurrence_starts_at', [$fromUtc->toDateTimeString(), $toUtc->toDateTimeString()])
            ->get()
            ->keyBy(fn ($o) => CarbonImmutable::parse($o->occurrence_starts_at)->utc()->toDateTimeString());

        // Non-repeating = single occurrence
        if (!($event->repeats ?? false) || empty($event->rrule)) {
            if (!$event->start) return [];
            $occUtc = CarbonImmutable::parse($event->start)->utc();
            if ($occUtc->lt($fromUtc) || $occUtc->gt($toUtc)) return [];

            $key = $occUtc->toDateTimeString();
            $override = $overrides->get($key);

            if ($override && $override->is_canceled) return [];

            $effStart = $override ? CarbonImmutable::parse($override->override_starts_at)->utc() : $occUtc;
            $effEnd = null;

            if ($override && $override->override_ends_at) {
                $effEnd = CarbonImmutable::parse($override->override_ends_at)->utc();
            } elseif ($durationSeconds) {
                $effEnd = $effStart->addSeconds($durationSeconds);
            }

            return [[
                'occurrence_id_utc' => $occUtc,
                'effective_start_utc' => $effStart,
                'effective_end_utc' => $effEnd,
            ]];
        }

        $set = RRule::createFromRfcString($event->rrule);

        $occList = null;
        if (method_exists($set, 'getOccurrencesBetween')) {
            $occList = $set->getOccurrencesBetween($fromUtc->toDateTime(), $toUtc->toDateTime(), $guard);
        }

        $out = [];
        $iter = $occList ?? $set;
        $i = 0;

        foreach ($iter as $occ) {
            if (++$i > $guard) break;

            $occUtc = CarbonImmutable::instance($occ)->utc();

            if ($occUtc->lt($fromUtc)) continue;
            if ($occUtc->gt($toUtc)) break;

            $key = $occUtc->toDateTimeString();
            $override = $overrides->get($key);

            if ($override && $override->is_canceled) continue;

            $effStart = $override ? CarbonImmutable::parse($override->override_starts_at)->utc() : $occUtc;

            $effEnd = null;
            if ($override && $override->override_ends_at) {
                $effEnd = CarbonImmutable::parse($override->override_ends_at)->utc();
            } elseif ($durationSeconds) {
                $effEnd = $effStart->addSeconds($durationSeconds);
            }

            $out[] = [
                'occurrence_id_utc' => $occUtc,
                'effective_start_utc' => $effStart,
                'effective_end_utc' => $effEnd,
            ];
        }

        return $out;
    }

    public function upcomingOccurrences(OrgEvent $event, int $limit = 20): array
    {
        if (!($event->repeats ?? false) || empty($event->rrule)) return [];

        $tz = $event->timezone ?: config('app.timezone', 'UTC');
        $from = CarbonImmutable::now('UTC')->subDay();
        $to = CarbonImmutable::now('UTC')->addYears(3);

        $rows = $this->occurrencesBetween($event, $from, $to, 8000);

        $out = [];
        foreach ($rows as $row) {
            if (count($out) >= $limit) break;
            $out[] = [
                'occurrence_starts_at' => $row['occurrence_id_utc']->toIso8601String(),
                'label_local' => $row['occurrence_id_utc']->setTimezone($tz)->toDayDateTimeString(),
            ];
        }

        return $out;
    }

    public function nextOccurrence(OrgEvent $event): ?array
    {
        $tz = $event->timezone ?: config('app.timezone', 'UTC');
        $from = CarbonImmutable::now('UTC')->subMinutes(1);
        $to = CarbonImmutable::now('UTC')->addYears(2);

        $rows = $this->occurrencesBetween($event, $from, $to, 5000);
        $first = $rows[0] ?? null;
        if (!$first) return null;

        return [
            'occurrence_id_utc' => $first['occurrence_id_utc'],
            'effective_start_utc' => $first['effective_start_utc'],
            'effective_end_utc' => $first['effective_end_utc'],
            'effective_start_local' => $first['effective_start_utc']->setTimezone($tz),
            'effective_end_local' => $first['effective_end_utc']?->setTimezone($tz),
        ];
    }
}
