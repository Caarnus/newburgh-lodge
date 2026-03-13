<?php

namespace App\Http\Requests\Officers;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficerAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.id' => ['required', 'integer', 'distinct', Rule::exists('lodge_officers', 'id')],
            'assignments.*.person_id' => ['nullable', 'integer', Rule::exists('member_profiles', 'person_id')],
        ];
    }
}

