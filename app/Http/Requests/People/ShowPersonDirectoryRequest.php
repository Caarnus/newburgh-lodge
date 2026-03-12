<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPersonDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $memberRole = RoleEnum::MEMBER->value;

        return $user->can(PeoplePermissions::VIEW_MEMBER_DETAILS)
            || $user->canAny(PeoplePermissions::directoryPermissions())
            || $user->hasRole($memberRole)
            || $user->hasRole(strtolower($memberRole));
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', Rule::in(['all', 'members', 'widows', 'orphans', 'relatives', 'others'])],
        ];
    }
}
