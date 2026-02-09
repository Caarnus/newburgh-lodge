<?php

namespace App\Listeners;

use App\Mail\NewUserRegisteredNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendNewUserRegistrationNotificationListener implements ShouldQueue
{
    public $queue = 'mail';
    public function handle(Registered $event): void
    {
        if (!config('site.new_user_notify_enabled')) {
            return;
        }

        $recipients = config('site.new_user_notify_emails', []);
        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->send(new NewUserRegisteredNotification($event->user));
    }
}
