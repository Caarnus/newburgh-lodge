<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class UnlinkUserFromPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage members') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'remove_member_role' => ['nullable', 'boolean'],
        ];
    }
}
