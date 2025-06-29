<?php

namespace App\Policies;

use App\Helpers\RoleEnum;
use App\Helpers\Utils;
use App\Models\JeopardyQuestion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JeopardyQuestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->degree !== RoleEnum::NONE;
    }

    public function view(User $user, JeopardyQuestion $jeopardyQuestion): bool
    {
        return Utils::checkDegree($user, $jeopardyQuestion->degree);
    }

    public function create(User $user): bool
    {
        return $user->officer || $user->admin;
    }

    public function update(User $user, JeopardyQuestion $jeopardyQuestion): bool
    {
        return $user->officer || $user->admin;
    }

    public function delete(User $user, JeopardyQuestion $jeopardyQuestion): bool
    {
        return $user->officer || $user->admin;
    }

    public function restore(User $user, JeopardyQuestion $jeopardyQuestion): bool
    {
        return $user->admin;
    }

    public function forceDelete(User $user, JeopardyQuestion $jeopardyQuestion): bool
    {
        return $user->admin;
    }
}
