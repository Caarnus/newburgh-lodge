<?php

namespace App\Helpers\People;

use App\Models\Person;
use App\Models\PersonContactLog;
use App\Models\PersonRelationship;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DirectoryPersonPresenter
{
    public static function member(Person $person): array
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

    public static function relative(Person $person): array
    {
        $relationship = self::firstRelationship($person);
        $isForwardRelationship = $relationship && (int) $relationship->person_id === (int) $person->id;
        $relatedPerson = $relationship
            ? ($isForwardRelationship ? $relationship->relatedPerson : $relationship->person)
            : null;
        $relationshipType = $relationship
            ? ($isForwardRelationship
                ? $relationship->relationship_type?->value
                : ($relationship->inverse_relationship_type?->value ?? $relationship->relationship_type?->value))
            : null;

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
            'relationship' => $relationship ? [
                'type' => $relationshipType,
                'label' => self::relationshipLabel($relationshipType),
                'is_primary' => (bool) $relationship->is_primary,
                'related_person' => $relatedPerson ? [
                    'id' => $relatedPerson->id,
                    'display_name' => $relatedPerson->display_name,
                    'member_number' => $relatedPerson->memberProfile?->member_number,
                ] : null,
            ] : null,
        ];
    }

    public static function detail(Person $person): array
    {
        return [
            'id' => $person->id,
            'display_name' => $person->display_name,
            'full_name' => $person->full_name,
            'preferred_name' => $person->preferred_name,
            'email' => $person->email,
            'phone' => $person->phone,
            'address_line_1' => $person->address_line_1,
            'address_line_2' => $person->address_line_2,
            'city' => $person->city,
            'state' => $person->state,
            'postal_code' => $person->postal_code,
            'birth_date' => self::date($person->birth_date),
            'notes' => $person->notes,
            'is_deceased' => (bool) $person->is_deceased,
            'death_date' => self::date($person->death_date),
            'last_contact_at' => self::dateTime(optional($person->contactLogs->first())->contacted_at),
            'member_profile' => $person->memberProfile ? [
                'member_number' => $person->memberProfile->member_number,
                'status' => $person->memberProfile->status,
                'member_type' => $person->memberProfile->member_type,
                'ea_date' => self::date($person->memberProfile->ea_date),
                'fc_date' => self::date($person->memberProfile->fc_date),
                'mm_date' => self::date($person->memberProfile->mm_date),
                'demit_date' => self::date($person->memberProfile->demit_date),
                'directory_visible' => (bool) $person->memberProfile->directory_visible,
                'can_auto_match_registration' => (bool) $person->memberProfile->can_auto_match_registration,
            ] : null,
            'classifications' => [
                'is_member' => $person->memberProfile !== null,
                'is_widow' => (bool) $person->getAttribute('is_widow'),
                'is_orphan' => (bool) $person->getAttribute('is_orphan'),
                'is_relative' => (bool) $person->getAttribute('is_relative'),
            ],
            'relationships' => self::serializeRelationships($person->relationships),
            'inverse_relationships' => self::serializeInverseRelationships($person->relatedTo),
            'contact_logs' => $person->contactLogs
                ->map(fn (PersonContactLog $log) => [
                    'id' => $log->id,
                    'contacted_at' => self::dateTime($log->contacted_at),
                    'contact_type' => $log->contact_type,
                    'notes' => $log->notes,
                    'created_by' => $log->creator?->name,
                ])
                ->values()
                ->all(),
        ];
    }

    protected static function carePerson(Person $person): array
    {
        $relationship = self::firstRelationship($person);
        $relatedPerson = $relationship?->relatedPerson;

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
            'related_member' => $relatedPerson ? [
                'id' => $relatedPerson->id,
                'display_name' => $relatedPerson->display_name,
                'member_number' => $relatedPerson->memberProfile?->member_number,
                'death_date' => self::date($relatedPerson->death_date),
            ] : null,
        ];
    }

    protected static function serializeRelationships(Collection $relationships): array
    {
        return $relationships
            ->sortByDesc('is_primary')
            ->map(fn (PersonRelationship $relationship) => [
                'id' => $relationship->id,
                'type' => $relationship->relationship_type?->value,
                'label' => self::relationshipLabel($relationship->relationship_type?->value),
                'is_primary' => (bool) $relationship->is_primary,
                'notes' => $relationship->notes,
                'person' => $relationship->relatedPerson ? [
                    'id' => $relationship->relatedPerson->id,
                    'display_name' => $relationship->relatedPerson->display_name,
                    'member_number' => $relationship->relatedPerson->memberProfile?->member_number,
                    'is_deceased' => (bool) $relationship->relatedPerson->is_deceased,
                ] : null,
            ])
            ->values()
            ->all();
    }

    protected static function serializeInverseRelationships(Collection $relationships): array
    {
        return $relationships
            ->sortByDesc('is_primary')
            ->map(fn (PersonRelationship $relationship) => [
                'id' => $relationship->id,
                'type' => $relationship->relationship_type?->value,
                'label' => self::relationshipLabel($relationship->relationship_type?->value),
                'is_primary' => (bool) $relationship->is_primary,
                'notes' => $relationship->notes,
                'person' => $relationship->person ? [
                    'id' => $relationship->person->id,
                    'display_name' => $relationship->person->display_name,
                    'member_number' => $relationship->person->memberProfile?->member_number,
                    'is_deceased' => (bool) $relationship->person->is_deceased,
                ] : null,
            ])
            ->values()
            ->all();
    }

    protected static function firstRelationship(Person $person): ?PersonRelationship
    {
        return $person->relationships->sortByDesc('is_primary')->first()
            ?: $person->relatedTo->sortByDesc('is_primary')->first();
    }

    protected static function relationshipLabel(?string $value): ?string
    {
        return $value ? Str::of($value)->replace('_', ' ')->title()->toString() : null;
    }

    protected static function date(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return self::toCarbon($value)?->toDateString();
    }

    protected static function dateTime(mixed $value): ?string
    {
        if (! $value) {
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

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        return null;
    }
}
