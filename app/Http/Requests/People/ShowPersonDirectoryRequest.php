<?php

namespace App\Http\Requests\People;

use App\Enums\MemberStatus;
use App\Enums\RelationshipType;
use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPersonDirectoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        if (is_string($status) && trim($status) !== '') {
            $this->merge([
                'status' => [trim($status)],
            ]);
        }
    }

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
        $relationshipTypes = array_map(
            fn (RelationshipType $type) => $type->value,
            RelationshipType::cases()
        );

        return [
            'from' => ['nullable', Rule::in(['all', 'members', 'widows', 'orphans', 'relatives', 'others'])],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'array'],
            'status.*' => ['required', Rule::in(MemberStatus::values())],
            'relationship_type' => ['nullable', Rule::in($relationshipTypes)],
            'has_email' => ['nullable', Rule::in(['yes', 'no'])],
            'has_phone' => ['nullable', Rule::in(['yes', 'no'])],
            'last_contact_older_than_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sort' => ['nullable', 'string', 'max:30'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
