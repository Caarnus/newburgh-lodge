<?php

namespace App\Http\Requests\Ritualist;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRitualCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::MANAGE_RITUALIST_PROGRAM) ?? false;
    }

    public function rules(): array
    {
        return [
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['integer', 'distinct', Rule::exists('ritual_programs', 'id')],
        ];
    }
}
