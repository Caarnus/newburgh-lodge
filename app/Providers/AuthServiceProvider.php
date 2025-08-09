<?php

namespace App\Providers;

use App\Models\Newsletter;
use App\Models\OrgEvent;
use App\Models\User;
use App\Policies\NewsletterPolicy;
use App\Policies\OrgEventPolicy;
use App\Policies\UserAdminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Newsletter::class => NewsletterPolicy::class,
        OrgEvent::class => OrgEventPolicy::class,
        User::class => UserAdminPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
