<?php

namespace App\Http\Requests\People;

use App\Helpers\People\PeoplePermissions;
use App\Models\Person;
use App\Models\PersonContactLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonContactLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person|null $person */
        $person = $this->route('person');
        /** @var PersonContactLog|null $contactLog */
        $contactLog = $this->route('contactLog');

        if (! $person || ! $contactLog) {
            return false;
        }

        if ((int) $contactLog->person_id !== (int) $person->id) {
            return false;
        }

        return $this->user()?->can(PeoplePermissions::EDIT_CARE_CONTACTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', Rule::in(['members', 'widows', 'orphans', 'relatives'])],
            'contacted_at' => ['required', 'date'],
            'contact_type' => ['nullable', Rule::in(['call', 'text', 'email', 'visit', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
