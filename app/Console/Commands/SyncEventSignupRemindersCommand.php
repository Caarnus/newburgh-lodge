<?php

namespace App\Console\Commands;

use App\Models\EventSignup;
use App\Services\EventSignupReminderService;
use Illuminate\Console\Command;

class SyncEventSignupRemindersCommand extends Command
{
    protected $signature = 'event-signups:sync-reminders {--days=90}';
    protected $description = 'Rolling-window sync of reminders for active signups';

    public function handle(EventSignupReminderService $service): int
    {
        $days = (int) $this->option('days');

        EventSignup::query()
            ->where('status', 'active')
            ->whereHas('page', fn ($q) => $q->where('is_enabled', true))
            ->with(['page.event', 'subscriber'])
            ->chunkById(200, function ($signups) use ($service, $days) {
                foreach ($signups as $signup) {
                    $service->syncForSignup($signup, $days);
                }
            });

        $this->info('Synced rolling-window reminders.');
        return self::SUCCESS;
    }
}
