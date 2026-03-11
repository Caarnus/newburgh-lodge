<?php

namespace App\Http\Requests\MemberImport;

use App\Helpers\People\PeoplePermissions;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRosterImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:15360'],
            'source_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->can(PeoplePermissions::IMPORT_MEMBER_ROSTER) ?? false;
    }
}
