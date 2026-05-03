<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('event-signups:send-reminders')->everyMinute();
Schedule::command('event-signups:sync-reminders --days=90')->dailyAt('02:10');
Schedule::command('volunteer-signups:send-reminders')->everyMinute();
Schedule::command('volunteer-signups:sync-reminders --days=90')->dailyAt('02:20');
