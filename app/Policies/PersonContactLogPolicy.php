<?php

namespace App\Policies;

use App\Helpers\People\PeoplePermissions;
use App\Models\PersonContactLog;
use App\Models\User;

class PersonContactLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::LOG_CARE_CONTACTS,
            PeoplePermissions::EDIT_CARE_CONTACTS,
        ]);
    }

    public function view(User $user, PersonContactLog $personContactLog): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PeoplePermissions::LOG_CARE_CONTACTS);
    }

    public function update(User $user, PersonContactLog $personContactLog): bool
    {
        return $user->can(PeoplePermissions::EDIT_CARE_CONTACTS);
    }

    public function delete(User $user, PersonContactLog $personContactLog): bool
    {
        return $user->can(PeoplePermissions::EDIT_CARE_CONTACTS);
    }
}
