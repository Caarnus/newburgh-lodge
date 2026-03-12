<?php

namespace App\Http\Requests\People;

use App\Enums\MemberStatus;
use App\Helpers\People\PeoplePermissions;
use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person|null $person */
        $person = $this->route('person');
        $user = $this->user();

        if (! $person || ! $user) {
            return false;
        }

        if ($user->can(PeoplePermissions::UPDATE_MEMBER_RECORDS)) {
            return true;
        }

        return $user->can(PeoplePermissions::UPDATE_OWN_PERSON_PROFILE)
            && (int) $user->person_id === (int) $person->id;
    }

    public function rules(): array
    {
        /** @var Person|null $person */
        $person = $this->route('person');
        $canManageRecords = $this->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS) ?? false;

        $rules = [
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'member_profile' => ['nullable', 'array'],
        ];

        if ($canManageRecords) {
            $rules = array_merge($rules, [
                'first_name' => ['required', 'string', 'max:120'],
                'middle_name' => ['nullable', 'string', 'max:120'],
                'last_name' => ['required', 'string', 'max:120'],
                'suffix' => ['nullable', 'string', 'max:50'],
                'birth_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string', 'max:4000'],
                'is_deceased' => ['nullable', 'boolean'],
                'death_date' => [
                    'nullable',
                    'date',
                    Rule::requiredIf(fn () => $this->boolean('is_deceased')),
                ],

                'member_profile.member_number' => [
                    'nullable',
                    'string',
                    'max:120',
                    Rule::unique('member_profiles', 'member_number')
                        ->ignore($person?->memberProfile?->id),
                ],
                'member_profile.status' => ['nullable', Rule::in(MemberStatus::values())],
                'member_profile.member_type' => ['prohibited'],
                'member_profile.ea_date' => ['nullable', 'date'],
                'member_profile.fc_date' => ['nullable', 'date'],
                'member_profile.mm_date' => ['nullable', 'date'],
                'member_profile.demit_date' => ['nullable', 'date'],
                'member_profile.can_auto_match_registration' => ['nullable', 'boolean'],
                'member_profile.directory_visible' => ['nullable', 'boolean'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'first_name' => ['prohibited'],
                'middle_name' => ['prohibited'],
                'last_name' => ['prohibited'],
                'suffix' => ['prohibited'],
                'birth_date' => ['prohibited'],
                'notes' => ['prohibited'],
                'is_deceased' => ['prohibited'],
                'death_date' => ['prohibited'],
                'member_profile.member_number' => ['prohibited'],
                'member_profile.status' => ['prohibited'],
                'member_profile.member_type' => ['prohibited'],
                'member_profile.ea_date' => ['prohibited'],
                'member_profile.fc_date' => ['prohibited'],
                'member_profile.mm_date' => ['prohibited'],
                'member_profile.demit_date' => ['prohibited'],
                'member_profile.can_auto_match_registration' => ['prohibited'],
                'member_profile.directory_visible' => ['prohibited'],
            ]);
        }

        return $rules;
    }
}
