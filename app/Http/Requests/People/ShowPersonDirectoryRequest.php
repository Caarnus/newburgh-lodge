<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPersonDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::VIEW_MEMBER_DETAILS) ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', Rule::in(['members', 'widows', 'orphans', 'relatives'])],
        ];
    }
}
