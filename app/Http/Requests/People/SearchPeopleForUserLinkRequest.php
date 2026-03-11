<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class SearchPeopleForUserLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage members') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
        ];
    }
}
