<?php

namespace App\Policies;

use App\Helpers\RoleEnum;
use App\Models\OrgEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrgEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user, OrgEvent $orgEvent): bool
    {
        if ($orgEvent->is_public) {
            return true;
        }

        // Members-only event viewing
        return $user?->hasPermissionTo('view event', 'web') ?? false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create event', 'web');
    }

    public function update(User $user, OrgEvent $orgEvent): bool
    {
        return $user->hasPermissionTo('update event', 'web');
    }

    public function delete(User $user, OrgEvent $orgEvent): bool
    {
        return $user->hasPermissionTo('delete event', 'web');
    }

    public function restore(User $user, OrgEvent $orgEvent): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function forceDelete(User $user, OrgEvent $orgEvent): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }
}
