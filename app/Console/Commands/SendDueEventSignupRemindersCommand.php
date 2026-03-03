<?php

namespace App\Console\Commands;

use App\Jobs\SendEventSignupReminderJob;
use App\Models\EventSignupReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendDueEventSignupRemindersCommand extends Command
{
    protected $signature = 'event-signups:send-reminders {--limit=200}';
    protected $description = 'Dispatch queued jobs for due event signup reminders';

    public function handle(): int
    {
        $nowUtc = CarbonImmutable::now('UTC');
        $limit = (int) $this->option('limit');

        $ids = EventSignupReminder::query()
            ->whereNull('sent_at')
            ->whereNull('canceled_at')
            ->where('send_at', '<=', $nowUtc->toDateTimeString())
            ->orderBy('send_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            SendEventSignupReminderJob::dispatch((int) $id)->onQueue('mail');
        }

        $this->info("Dispatched {$ids->count()} reminder job(s).");
        return self::SUCCESS;
    }
}
