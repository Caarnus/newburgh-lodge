<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class LinkUserToPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage members') ?? false;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
