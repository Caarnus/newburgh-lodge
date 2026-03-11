<?php

namespace App\Helpers\People;

use App\Models\Person;
use App\Models\PersonRelationship;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DirectoryPersonPresenter
{
    public static function member(Person $person): array
    {
        return [
            ...self::basePerson($person),
            'member_profile' => $person->memberProfile ? [
                'member_number' => $person->memberProfile->member_number,
                'status' => $person->memberProfile->status,
                'member_type' => $person->memberProfile->member_type,
                'directory_visible' => (bool) $person->memberProfile->directory_visible,
                'ea_date' => self::date($person->memberProfile->ea_date),
                'fc_date' => self::date($person->memberProfile->fc_date),
                'mm_date' => self::date($person->memberProfile->mm_date),
                'demit_date' => self::date($person->memberProfile->demit_date),
            ] : null,
        ];
    }

    public static function widow(Person $person): array
    {
        return self::carePerson($person);
    }

    public static function orphan(Person $person): array
    {
        return self::carePerson($person);
    }

    protected static function carePerson(Person $person): array
    {
        $relationship = self::firstRelatedRelationship($person);
        $relatedPerson = $relationship?->relatedPerson;

        return [
            ...self::basePerson($person),
            'related_member' => $relatedPerson ? [
                'id' => $relatedPerson->id,
                'display_name' => $relatedPerson->display_name,
                'member_number' => $relatedPerson->memberProfile?->member_number,
                'death_date' => self::date($relatedPerson->death_date),
            ] : null,
        ];
    }

    protected static function basePerson(Person $person): array
    {
        return [
            'id' => $person->id,
            'display_name' => $person->display_name,
            'full_name' => $person->full_name,
            'email' => $person->email,
            'phone' => $person->phone,
            'city' => $person->city,
            'state' => $person->state,
            'is_deceased' => (bool) $person->is_deceased,
            'death_date' => self::date($person->death_date),
            'last_contact_at' => self::dateTime($person->getAttribute('last_contact_at')),
        ];
    }

    protected static function firstRelatedRelationship(Person $person): ?PersonRelationship
    {
        return $person->relationships->sortByDesc('is_primary')->first();
    }

    protected static function date(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        return self::toCarbon($value)?->toDateString();
    }

    protected static function dateTime(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        return self::toCarbon($value)?->toDateTimeString();
    }

    protected static function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
