<?php

namespace App\Http\Requests\People;

use App\Enums\RelationshipType;
use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
    }

    public function rules(): array
    {
        $relationshipTypes = array_map(
            fn (RelationshipType $type) => $type->value,
            RelationshipType::cases()
        );

        return [
            'record_type' => ['required', Rule::in(['person', 'member', 'relative'])],

            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_deceased' => ['nullable', 'boolean'],
            'death_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->boolean('is_deceased')),
            ],

            'member_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('member_profiles', 'member_number'),
                Rule::requiredIf(fn () => $this->string('record_type')->toString() === 'member'),
            ],
            'member_status' => ['nullable', 'string', 'max:120'],
            'member_type' => ['nullable', 'string', 'max:120'],
            'ea_date' => ['nullable', 'date'],
            'fc_date' => ['nullable', 'date'],
            'mm_date' => ['nullable', 'date'],
            'demit_date' => ['nullable', 'date'],
            'can_auto_match_registration' => ['nullable', 'boolean'],
            'directory_visible' => ['nullable', 'boolean'],

            'related_person_id' => [
                'nullable',
                'integer',
                'exists:people,id',
                Rule::requiredIf(fn () => $this->string('record_type')->toString() === 'relative'),
            ],
            'relationship_type' => [
                'nullable',
                Rule::in($relationshipTypes),
                Rule::requiredIf(fn () => $this->string('record_type')->toString() === 'relative'),
            ],
            'inverse_relationship_type' => ['nullable', Rule::in($relationshipTypes)],
            'relationship_is_primary' => ['nullable', 'boolean'],
            'relationship_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
