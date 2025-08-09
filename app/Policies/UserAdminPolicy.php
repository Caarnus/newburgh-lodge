<?php

namespace App\Policies;

use App\Helpers\RoleEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserAdminPolicy
{
    use HandlesAuthorization;

    public function access(User $user): bool
    {
        return $user->hasRole(RoleEnum::ADMIN) || $user->hasRole(RoleEnum::SECRETARY);
    }
}
