<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAllPeopleDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $memberRole = RoleEnum::MEMBER->value;

        return $user->canAny(PeoplePermissions::directoryPermissions())
            || $user->hasRole($memberRole)
            || $user->hasRole(strtolower($memberRole));
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'has_email' => ['nullable', Rule::in(['yes', 'no'])],
            'has_phone' => ['nullable', Rule::in(['yes', 'no'])],
            'last_contact_older_than_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'hide_deceased' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'name',
                '-name',
                'last_contact',
                '-last_contact',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
