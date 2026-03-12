<?php

namespace App\Http\Requests\People;

use App\Enums\RelationshipType;
use App\Helpers\People\PeoplePermissions;
use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
    }

    public function rules(): array
    {
        /** @var Person|null $person */
        $person = $this->route('person');
        $relationshipTypes = array_map(
            fn (RelationshipType $type) => $type->value,
            RelationshipType::cases()
        );

        return [
            'from' => ['nullable', Rule::in(['members', 'widows', 'orphans', 'relatives'])],
            'related_person_mode' => ['required', Rule::in(['existing', 'new'])],

            'related_person_id' => [
                'nullable',
                'integer',
                'exists:people,id',
                Rule::requiredIf(fn () => $this->string('related_person_mode')->toString() === 'existing'),
                Rule::notIn(array_filter([(int) ($person?->id ?? 0)])),
            ],

            'new_person_first_name' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => $this->string('related_person_mode')->toString() === 'new'),
            ],
            'new_person_middle_name' => ['nullable', 'string', 'max:120'],
            'new_person_last_name' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => $this->string('related_person_mode')->toString() === 'new'),
            ],
            'new_person_suffix' => ['nullable', 'string', 'max:50'],
            'new_person_preferred_name' => ['nullable', 'string', 'max:120'],
            'new_person_email' => ['nullable', 'email', 'max:255'],
            'new_person_phone' => ['nullable', 'string', 'max:50'],
            'new_person_notes' => ['nullable', 'string', 'max:1000'],
            'new_person_is_deceased' => ['nullable', 'boolean'],
            'new_person_death_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->boolean('new_person_is_deceased')),
            ],

            'relationship_type' => [
                'required',
                Rule::in($relationshipTypes),
                Rule::when(
                    fn () => $this->string('related_person_mode')->toString() === 'existing' && $this->filled('related_person_id'),
                    Rule::unique('person_relationships', 'relationship_type')
                        ->where(fn ($query) => $query
                            ->where('person_id', $person?->id)
                            ->where('related_person_id', $this->integer('related_person_id')))
                ),
            ],
            'inverse_relationship_type' => ['nullable', Rule::in($relationshipTypes)],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
