<?php

namespace App\Providers;

use App\Helpers\RoleEnum;
use App\Models\ContentTile;
use App\Models\Newsletter;
use App\Models\OrgEvent;
use App\Models\User;
use App\Policies\NewsletterPolicy;
use App\Policies\OrgEventPolicy;
use App\Policies\UserAdminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        Gate::define('manage-content', function ($user) {
            if (method_exists($user, 'can')) {
                try { return $user->can('manage-content'); } catch (\Throwable $e) {}
            }
            return in_array($user->role ?? '', [RoleEnum::OFFICER,RoleEnum::SECRETARY,RoleEnum::ADMIN]);
        });
    }
}
