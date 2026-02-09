<?php

namespace App\Providers;

use App\Listeners\AssignDefaultRoleListener;
use App\Listeners\SendNewUserRegistrationNotificationListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            AssignDefaultRoleListener::class,
            SendNewUserRegistrationNotificationListener::class,
        ],
    ];
}
