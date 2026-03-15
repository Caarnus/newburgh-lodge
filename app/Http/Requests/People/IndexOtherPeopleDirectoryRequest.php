<?php

namespace App\Http\Requests\People;

use App\Enums\MemberStatus;
use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOtherPeopleDirectoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        if (is_string($status)) {
            $status = trim($status) !== '' ? [trim($status)] : [];
        }

        if (is_array($status)) {
            $status = array_values(array_filter(
                array_map(static fn ($value) => is_string($value) ? trim($value) : null, $status),
                static fn ($value) => is_string($value) && $value !== ''
            ));
        } else {
            $status = [];
        }

        if ($status === []) {
            $status = MemberStatus::defaultDirectoryFilters();
        }

        $this->merge([
            'status' => $status,
        ]);
    }

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
            'status' => ['nullable', 'array'],
            'status.*' => ['required', Rule::in(MemberStatus::values())],
            'has_email' => ['nullable', Rule::in(['yes', 'no'])],
            'has_phone' => ['nullable', Rule::in(['yes', 'no'])],
            'last_contact_older_than_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
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
