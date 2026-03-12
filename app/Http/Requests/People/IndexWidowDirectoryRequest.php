<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWidowDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $memberRole = RoleEnum::MEMBER->value;

        return $user->can(PeoplePermissions::VIEW_WIDOW_DIRECTORY)
            || $user->can(PeoplePermissions::VIEW_MEMBER_DIRECTORY)
            || $user->hasRole($memberRole)
            || $user->hasRole(strtolower($memberRole));
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'hide_deceased' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'name',
                '-name',
                'death_date',
                '-death_date',
                'last_contact',
                '-last_contact',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
