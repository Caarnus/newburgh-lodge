<?php

namespace App\Console\Commands;

use App\Models\VolunteerSignupSheet;
use App\Services\VolunteerSignupReminderService;
use Illuminate\Console\Command;

class SyncVolunteerSignupRemindersCommand extends Command
{
    protected $signature = 'volunteer-signups:sync-reminders {--days=90}';
    protected $description = 'Rolling-window sync of reminders for volunteer signups';

    public function handle(VolunteerSignupReminderService $service): int
    {
        $days = (int) $this->option('days');

        VolunteerSignupSheet::query()
            ->where('is_enabled', true)
            ->whereHas('event')
            ->chunkById(100, function ($sheets) use ($service, $days) {
                foreach ($sheets as $sheet) {
                    $service->syncForSheet($sheet, $days);
                }
            });

        $this->info('Synced volunteer signup reminders.');
        return self::SUCCESS;
    }
}
