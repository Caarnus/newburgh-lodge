<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonContactLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::LOG_CARE_CONTACTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', Rule::in(['members', 'widows', 'orphans', 'relatives'])],
            'contacted_at' => ['nullable', 'date'],
            'contact_type' => ['nullable', Rule::in(['call', 'text', 'email', 'visit', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
