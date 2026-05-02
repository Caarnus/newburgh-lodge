<?php

namespace App\Http\Requests\Officers;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPastMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'string', 'max:32'],
            'deceased' => ['nullable', 'boolean'],
            'person_id' => ['nullable', 'integer', Rule::exists('member_profiles', 'person_id')],
        ];
    }
}
