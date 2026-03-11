<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMemberDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage members') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:100'],
            'member_type' => ['nullable', 'string', 'max:100'],
            'hide_deceased' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in([
                'name',
                '-name',
                'member_number',
                '-member_number',
                'status',
                '-status',
                'last_contact',
                '-last_contact',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
