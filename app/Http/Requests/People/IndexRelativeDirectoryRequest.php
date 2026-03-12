<?php

namespace App\Http\Requests\People;

use App\Enums\RelationshipType;
use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRelativeDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAny(PeoplePermissions::directoryPermissions()) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'relationship_type' => ['nullable', Rule::in(array_map(fn (RelationshipType $type) => $type->value, RelationshipType::cases()))],
            'hide_deceased' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'name',
                '-name',
                'relationship',
                '-relationship',
                'last_contact',
                '-last_contact',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
