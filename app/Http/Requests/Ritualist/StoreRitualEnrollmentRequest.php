<?php

namespace App\Http\Requests\Ritualist;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRitualEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM) ?? false;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', Rule::exists('member_profiles', 'person_id')],
        ];
    }
}
