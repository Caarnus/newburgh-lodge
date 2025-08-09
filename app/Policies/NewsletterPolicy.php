<?php

namespace App\Policies;

use App\Helpers\RoleEnum;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsletterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Newsletter $newsletter): bool
    {
        return $newsletter->is_public || ($user && $user->can('view newsletter'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::OFFICER->value)
            || $user->hasRole(RoleEnum::ADMIN->value)
            || $user->hasRole(RoleEnum::SECRETARY->value);
    }

    public function update(User $user, Newsletter $newsletter): bool
    {
        return $user->hasRole(RoleEnum::OFFICER->value)
            || $user->hasRole(RoleEnum::ADMIN->value)
            || $user->hasRole(RoleEnum::SECRETARY->value);
    }

    public function delete(User $user, Newsletter $newsletter): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value)
            || $user->hasRole(RoleEnum::SECRETARY->value);
    }

    public function restore(User $user, Newsletter $newsletter): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }

    public function forceDelete(User $user, Newsletter $newsletter): bool
    {
        return $user->hasRole(RoleEnum::ADMIN->value);
    }
}
