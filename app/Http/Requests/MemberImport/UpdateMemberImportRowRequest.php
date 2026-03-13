<?php

namespace App\Http\Requests\MemberImport;

use App\Enums\MemberStatus;
use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberImportRowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'member_number' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(MemberStatus::values())],
            'first_name' => ['nullable', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'ea_date' => ['nullable', 'date'],
            'fc_date' => ['nullable', 'date'],
            'mm_date' => ['nullable', 'date'],
            'honorary_date' => ['nullable', 'date'],
            'demit_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date'],
            'is_deceased' => ['nullable', 'boolean'],
            'past_master' => ['nullable', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::IMPORT_MEMBER_ROSTER) ?? false;
    }
}

