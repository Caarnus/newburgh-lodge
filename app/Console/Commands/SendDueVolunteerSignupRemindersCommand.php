<?php

namespace App\Console\Commands;

use App\Jobs\SendVolunteerSignupReminderJob;
use App\Models\VolunteerSignupReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendDueVolunteerSignupRemindersCommand extends Command
{
    protected $signature = 'volunteer-signups:send-reminders {--limit=200}';
    protected $description = 'Dispatch queued jobs for due volunteer signup reminders';

    public function handle(): int
    {
        $nowUtc = CarbonImmutable::now('UTC');
        $limit = (int) $this->option('limit');

        $ids = VolunteerSignupReminder::query()
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('send_at', '<=', $nowUtc->toDateTimeString())
            ->orderBy('send_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            SendVolunteerSignupReminderJob::dispatch((int) $id)->onQueue('mail');
        }

        $this->info("Dispatched {$ids->count()} volunteer reminder job(s).");
        return self::SUCCESS;
    }
}
