<?php

namespace App\Policies;

use App\Helpers\RoleEnum;
use App\Models\OrgEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrgEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OrgEvent $orgEvent): bool
    {
        return $orgEvent->is_public || ($user->can('view event'));
    }

    public function create(User $user): bool
    {
        return $user->can('create event');
    }

    public function update(User $user, OrgEvent $orgEvent): bool
    {
        return $user->can('update event');
    }

    public function delete(User $user, OrgEvent $orgEvent): bool
    {
        return $user->can('delete event');
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
