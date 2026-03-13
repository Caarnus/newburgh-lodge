<?php

namespace App\Http\Requests\People;

use App\Enums\RelationshipType;
use App\Helpers\People\PeoplePermissions;
use App\Models\Person;
use App\Models\PersonRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person|null $person */
        $person = $this->route('person');
        /** @var PersonRelationship|null $relationship */
        $relationship = $this->route('relationship');

        if (! $person || ! $relationship) {
            return false;
        }

        $isAttachedToPerson = (int) $relationship->person_id === (int) $person->id
            || (int) $relationship->related_person_id === (int) $person->id;

        if (! $isAttachedToPerson) {
            return false;
        }

        return $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;
    }

    public function rules(): array
    {
        /** @var PersonRelationship|null $relationship */
        $relationship = $this->route('relationship');
        $relationshipTypes = array_map(
            fn (RelationshipType $type) => $type->value,
            RelationshipType::cases()
        );

        return [
            'from' => ['nullable', Rule::in(['all', 'members', 'widows', 'orphans', 'relatives', 'others'])],

            'relationship_type' => [
                'required',
                Rule::in($relationshipTypes),
                Rule::unique('person_relationships', 'relationship_type')
                    ->where(fn ($query) => $query
                        ->where('person_id', $relationship?->person_id)
                        ->where('related_person_id', $relationship?->related_person_id))
                    ->ignore($relationship?->id),
            ],
            'inverse_relationship_type' => ['nullable', Rule::in($relationshipTypes)],
            'anniversary_date' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
