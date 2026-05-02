<?php

namespace App\Http\Requests\Ritualist;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertRitualProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'points' => ['required', 'integer', 'min:0'],
            'degree_group' => ['required', Rule::in(['entered_apprentice', 'fellow_craft', 'master_mason', 'optional'])],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
