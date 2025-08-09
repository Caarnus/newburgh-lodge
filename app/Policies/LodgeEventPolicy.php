<?php

namespace App\Policies;

use App\Models\LodgeEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LodgeEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {

    }

    public function view(User $user, LodgeEvent $lodgeEvent): bool
    {
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, LodgeEvent $lodgeEvent): bool
    {
    }

    public function delete(User $user, LodgeEvent $lodgeEvent): bool
    {
    }

    public function restore(User $user, LodgeEvent $lodgeEvent): bool
    {
    }

    public function forceDelete(User $user, LodgeEvent $lodgeEvent): bool
    {
    }
}
