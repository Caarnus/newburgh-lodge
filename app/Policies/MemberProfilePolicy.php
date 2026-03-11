<?php

namespace App\Policies;

use App\Helpers\People\PeoplePermissions;
use App\Models\MemberProfile;
use App\Models\User;

class MemberProfilePolicy
{
    public function view(User $user, MemberProfile $memberProfile): bool
    {
        if ($user->can(PeoplePermissions::VIEW_MEMBER_DETAILS)) {
            return true;
        }

        return $user->can(PeoplePermissions::VIEW_OWN_PERSON_PROFILE)
            && (int) $user->person_id === (int) $memberProfile->person_id;
    }

    public function update(User $user, MemberProfile $memberProfile): bool
    {
        if ($user->can(PeoplePermissions::UPDATE_MEMBER_RECORDS)) {
            return true;
        }

        return $user->can(PeoplePermissions::UPDATE_OWN_PERSON_PROFILE)
            && (int) $user->person_id === (int) $memberProfile->person_id;
    }
}
