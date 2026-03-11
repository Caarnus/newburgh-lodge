<?php

namespace App\Policies;

use App\Helpers\People\PeoplePermissions;
use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([
            PeoplePermissions::VIEW_MEMBER_DIRECTORY,
            PeoplePermissions::VIEW_WIDOW_DIRECTORY,
            PeoplePermissions::VIEW_ORPHAN_DIRECTORY,
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::VIEW_OWN_PERSON_PROFILE,
        ]);
    }

    public function view(User $user, Person $person): bool
    {
        if ($user->can(PeoplePermissions::VIEW_MEMBER_DETAILS)) {
            return true;
        }

        return $user->can(PeoplePermissions::VIEW_OWN_PERSON_PROFILE)
            && (int) $user->person_id === (int) $person->id;
    }

    public function update(User $user, Person $person): bool
    {
        if ($user->can(PeoplePermissions::UPDATE_MEMBER_RECORDS)) {
            return true;
        }

        return $user->can(PeoplePermissions::UPDATE_OWN_PERSON_PROFILE)
            && (int) $user->person_id === (int) $person->id;
    }

    public function export(User $user): bool
    {
        return $user->can(PeoplePermissions::EXPORT_MEMBER_DIRECTORY);
    }
}
