<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;

class ShowSelfPersonProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->person_id) {
            return false;
        }

        return $user->can(PeoplePermissions::VIEW_OWN_PERSON_PROFILE)
            || $user->can(PeoplePermissions::UPDATE_OWN_PERSON_PROFILE)
            || $user->can(PeoplePermissions::UPDATE_MEMBER_RECORDS);
    }

    public function rules(): array
    {
        return [];
    }
}
