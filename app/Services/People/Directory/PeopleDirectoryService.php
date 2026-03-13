<?php

namespace App\Services\People\Directory;

use App\Enums\MemberStatus;
use App\Enums\RelationshipType;
use App\Models\Person;
use App\Models\PersonContactLog;
use App\Models\PersonRelationship;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PeopleDirectoryService
{
    public function paginateMembers(array $filters): LengthAwarePaginator
    {
        return $this->memberQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportMembers(array $filters): Collection
    {
        return $this->memberQuery($filters)->get();
    }

    public function paginateWidows(array $filters): LengthAwarePaginator
    {
        return $this->widowQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportWidows(array $filters): Collection
    {
        return $this->widowQuery($filters)->get();
    }

    public function paginateOrphans(array $filters): LengthAwarePaginator
    {
        return $this->orphanQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportOrphans(array $filters): Collection
    {
        return $this->orphanQuery($filters)->get();
    }

    public function paginateRelatives(array $filters): LengthAwarePaginator
    {
        return $this->relativeQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportRelatives(array $filters): Collection
    {
        return $this->relativeQuery($filters)->get();
    }

    public function paginateOtherPeople(array $filters): LengthAwarePaginator
    {
        return $this->otherPeopleQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportOtherPeople(array $filters): Collection
    {
        return $this->otherPeopleQuery($filters)->get();
    }

    public function paginateAllPeople(array $filters): LengthAwarePaginator
    {
        return $this->allPeopleQuery($filters)
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function exportAllPeople(array $filters): Collection
    {
        return $this->allPeopleQuery($filters)->get();
    }

    public function findPersonForDirectory(int $personId): Person
    {
        $person = Person::query()
            ->with([
                'memberProfile',
                'relationships.relatedPerson.memberProfile',
                'relatedTo.person.memberProfile',
                'contactLogs.creator',
            ])
            ->find($personId);

        if (! $person) {
            throw (new ModelNotFoundException())->setModel(Person::class, [$personId]);
        }

        $person->setAttribute('is_widow', $this->personMatchesConstraint($person, $this->widowConstraint()));
        $person->setAttribute('is_orphan', $this->personMatchesConstraint($person, $this->orphanConstraint()));
        $person->setAttribute('is_relative', ! $person->memberProfile && ($person->relationships->isNotEmpty() || $person->relatedTo->isNotEmpty()));

        return $person;
    }

    public function memberStatusOptions(): array
    {
        return MemberStatus::options();
    }

    public function relationshipTypeOptions(): array
    {
        return collect(RelationshipType::cases())
            ->map(fn (RelationshipType $type) => [
                'label' => Str::of($type->value)->replace('_', ' ')->title()->toString(),
                'value' => $type->value,
            ])
            ->values()
            ->all();
    }

    public function memberSortOptions(): array
    {
        return [
            ['label' => 'Name (A–Z)', 'value' => 'name'],
            ['label' => 'Name (Z–A)', 'value' => '-name'],
            ['label' => 'Member Number', 'value' => 'member_number'],
            ['label' => 'Member Number (Desc)', 'value' => '-member_number'],
            ['label' => 'Status', 'value' => 'status'],
            ['label' => 'Status (Desc)', 'value' => '-status'],
            ['label' => 'Most Recently Contacted', 'value' => '-last_contact'],
            ['label' => 'Least Recently Contacted', 'value' => 'last_contact'],
        ];
    }

    public function careSortOptions(): array
    {
        return [
            ['label' => 'Name (A–Z)', 'value' => 'name'],
            ['label' => 'Name (Z–A)', 'value' => '-name'],
            ['label' => 'Most Recent Death Date', 'value' => '-death_date'],
            ['label' => 'Oldest Death Date', 'value' => 'death_date'],
            ['label' => 'Most Recently Contacted', 'value' => '-last_contact'],
            ['label' => 'Least Recently Contacted', 'value' => 'last_contact'],
        ];
    }

    public function relativeSortOptions(): array
    {
        return [
            ['label' => 'Name (A–Z)', 'value' => 'name'],
            ['label' => 'Name (Z–A)', 'value' => '-name'],
            ['label' => 'Relationship', 'value' => 'relationship'],
            ['label' => 'Relationship (Desc)', 'value' => '-relationship'],
            ['label' => 'Most Recently Contacted', 'value' => '-last_contact'],
            ['label' => 'Least Recently Contacted', 'value' => 'last_contact'],
        ];
    }

    public function peopleSortOptions(): array
    {
        return [
            ['label' => 'Name (A–Z)', 'value' => 'name'],
            ['label' => 'Name (Z–A)', 'value' => '-name'],
            ['label' => 'Most Recently Contacted', 'value' => '-last_contact'],
            ['label' => 'Least Recently Contacted', 'value' => 'last_contact'],
        ];
    }

    protected function perPage(array $filters): int
    {
        return max(10, min(100, (int) ($filters['per_page'] ?? 25)));
    }

    protected function memberQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->leftJoin('member_profiles', 'member_profiles.person_id', '=', 'people.id')
            ->whereNotNull('member_profiles.id')
            ->with('memberProfile')
            ->selectSub($this->latestContactSubquery(), 'last_contact_at');

        $this->applyCommonSearch($query, $filters['q'] ?? null, includeMemberNumber: true);
        $this->applyDirectoryFilters($query, $filters);

        $selectedStatuses = $filters['status'] ?? null;
        if (is_array($selectedStatuses) && $selectedStatuses !== []) {
            $query->whereIn('member_profiles.status', $selectedStatuses);
        }

        $this->applyMemberSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function widowQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->with(['relationships' => $this->careRelationshipLoader(RelationshipType::Spouse)])
            ->selectSub($this->latestContactSubquery(), 'last_contact_at')
            ->selectSub($this->relatedDeceasedDeathDateSubquery(RelationshipType::Spouse), 'related_member_death_date')
            ->where($this->widowConstraint());

        $this->applyCommonSearch($query, $filters['q'] ?? null);
        $this->applyDirectoryFilters($query, $filters);
        $this->applyCareSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function orphanQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->with(['relationships' => $this->careRelationshipLoader(RelationshipType::Parent)])
            ->selectSub($this->latestContactSubquery(), 'last_contact_at')
            ->selectSub($this->relatedDeceasedDeathDateSubquery(RelationshipType::Parent), 'related_member_death_date')
            ->where($this->orphanConstraint());

        $this->applyCommonSearch($query, $filters['q'] ?? null);
        $this->applyDirectoryFilters($query, $filters);
        $this->applyCareSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function relativeQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->with([
                'relationships.relatedPerson.memberProfile',
                'relatedTo.person.memberProfile',
            ])
            ->whereDoesntHave('memberProfile')
            ->where(function (Builder $builder) use ($filters) {
                $builder->whereHas('relationships', function (Builder $relationshipQuery) use ($filters) {
                    if (filled($filters['relationship_type'] ?? null)) {
                        $relationshipQuery->where('relationship_type', $filters['relationship_type']);
                    }
                })->orWhereHas('relatedTo', function (Builder $relationshipQuery) use ($filters) {
                    if (filled($filters['relationship_type'] ?? null)) {
                        $relationshipQuery->where('relationship_type', $filters['relationship_type']);
                    }
                });
            })
            ->whereNot($this->widowConstraint())
            ->whereNot($this->orphanConstraint())
            ->selectSub($this->latestContactSubquery(), 'last_contact_at')
            ->selectSub($this->primaryRelationshipTypeSubquery(), 'primary_relationship_type');

        $this->applyCommonSearch($query, $filters['q'] ?? null);
        $this->applyDirectoryFilters($query, $filters);
        $this->applyRelativeSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function otherPeopleQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->with('memberProfile')
            ->whereDoesntHave('memberProfile')
            ->whereDoesntHave('relationships')
            ->whereDoesntHave('relatedTo')
            ->selectSub($this->latestContactSubquery(), 'last_contact_at');

        $this->applyCommonSearch($query, $filters['q'] ?? null);
        $this->applyDirectoryFilters($query, $filters);
        $this->applyPersonSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function allPeopleQuery(array $filters): Builder
    {
        $query = Person::query()
            ->select('people.*')
            ->leftJoin('member_profiles', 'member_profiles.person_id', '=', 'people.id')
            ->with('memberProfile')
            ->selectSub($this->latestContactSubquery(), 'last_contact_at');

        $this->applyCommonSearch($query, $filters['q'] ?? null, includeMemberNumber: true);
        $this->applyDirectoryFilters($query, $filters);
        $this->applyPersonSort($query, $filters['sort'] ?? 'name');

        return $query;
    }

    protected function applyCommonSearch(Builder $query, ?string $term, bool $includeMemberNumber = false): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $lowerTerm = mb_strtolower($term);

        $query->where(function (Builder $builder) use ($term, $lowerTerm, $includeMemberNumber) {
            $builder->whereRaw('LOWER(people.first_name) like ?', ["%{$lowerTerm}%"])
                ->orWhereRaw('LOWER(people.middle_name) like ?', ["%{$lowerTerm}%"])
                ->orWhereRaw('LOWER(people.last_name) like ?', ["%{$lowerTerm}%"])
                ->orWhereRaw('LOWER(people.preferred_name) like ?', ["%{$lowerTerm}%"])
                ->orWhereRaw('LOWER(people.display_name_override) like ?', ["%{$lowerTerm}%"])
                ->orWhereRaw('LOWER(people.email) like ?', ["%{$lowerTerm}%"])
                ->orWhere('people.phone', 'like', "%{$term}%")
                ->orWhereRaw("LOWER(CONCAT(COALESCE(people.first_name, ''), ' ', COALESCE(people.last_name, ''))) like ?", ["%{$lowerTerm}%"]);

            if ($includeMemberNumber) {
                $builder->orWhere('member_profiles.member_number', 'like', "%{$term}%");
            }
        });
    }

    protected function applyDirectoryFilters(Builder $query, array $filters): void
    {
        $hideDeceased = filter_var(
            $filters['hide_deceased'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;

        $query
            ->hideDeceased($hideDeceased)
            ->whereHasEmailValue($filters['has_email'] ?? null)
            ->whereHasPhoneValue($filters['has_phone'] ?? null)
            ->whereLastContactOlderThanDays(isset($filters['last_contact_older_than_days'])
                ? (int) $filters['last_contact_older_than_days']
                : null);
    }

    protected function applyMemberSort(Builder $query, string $sort): void
    {
        match ($sort) {
            '-name' => $query->orderByDesc('people.last_name')->orderByDesc('people.first_name'),
            'member_number' => $query->orderBy('member_profiles.member_number')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-member_number' => $query->orderByDesc('member_profiles.member_number')->orderBy('people.last_name')->orderBy('people.first_name'),
            'status' => $query->orderBy('member_profiles.status')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-status' => $query->orderByDesc('member_profiles.status')->orderBy('people.last_name')->orderBy('people.first_name'),
            'last_contact' => $query->orderBy('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-last_contact' => $query->orderByDesc('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            default => $query->orderBy('people.last_name')->orderBy('people.first_name'),
        };
    }

    protected function applyCareSort(Builder $query, string $sort): void
    {
        match ($sort) {
            '-name' => $query->orderByDesc('people.last_name')->orderByDesc('people.first_name'),
            'death_date' => $query->orderBy('related_member_death_date')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-death_date' => $query->orderByDesc('related_member_death_date')->orderBy('people.last_name')->orderBy('people.first_name'),
            'last_contact' => $query->orderBy('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-last_contact' => $query->orderByDesc('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            default => $query->orderBy('people.last_name')->orderBy('people.first_name'),
        };
    }

    protected function applyRelativeSort(Builder $query, string $sort): void
    {
        match ($sort) {
            '-name' => $query->orderByDesc('people.last_name')->orderByDesc('people.first_name'),
            'relationship' => $query->orderBy('primary_relationship_type')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-relationship' => $query->orderByDesc('primary_relationship_type')->orderBy('people.last_name')->orderBy('people.first_name'),
            'last_contact' => $query->orderBy('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-last_contact' => $query->orderByDesc('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            default => $query->orderBy('people.last_name')->orderBy('people.first_name'),
        };
    }

    protected function applyPersonSort(Builder $query, string $sort): void
    {
        match ($sort) {
            '-name' => $query->orderByDesc('people.last_name')->orderByDesc('people.first_name'),
            'last_contact' => $query->orderBy('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            '-last_contact' => $query->orderByDesc('last_contact_at')->orderBy('people.last_name')->orderBy('people.first_name'),
            default => $query->orderBy('people.last_name')->orderBy('people.first_name'),
        };
    }

    protected function latestContactSubquery(): Builder
    {
        return PersonContactLog::query()
            ->selectRaw('MAX(contacted_at)')
            ->whereColumn('person_contact_logs.person_id', 'people.id');
    }

    protected function primaryRelationshipTypeSubquery(): Builder
    {
        return PersonRelationship::query()
            ->select('relationship_type')
            ->whereColumn('person_relationships.person_id', 'people.id')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->limit(1);
    }

    protected function relatedDeceasedDeathDateSubquery(RelationshipType $relationshipType): Builder
    {
        return PersonRelationship::query()
            ->select('related_people.death_date')
            ->join('people as related_people', 'related_people.id', '=', 'person_relationships.related_person_id')
            ->join('member_profiles as related_member_profiles', 'related_member_profiles.person_id', '=', 'related_people.id')
            ->whereColumn('person_relationships.person_id', 'people.id')
            ->where('person_relationships.relationship_type', $relationshipType->value)
            ->where('related_people.is_deceased', true)
            ->orderByDesc('person_relationships.is_primary')
            ->orderBy('person_relationships.id')
            ->limit(1);
    }

    protected function careRelationshipLoader(RelationshipType $relationshipType): \Closure
    {
        return function (Builder|HasMany $relationshipQuery) use ($relationshipType) {
            $relationshipQuery
                ->where('relationship_type', $relationshipType->value)
                ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                    $relatedQuery
                        ->where('is_deceased', true)
                        ->whereHas('memberProfile');
                })
                ->with(['relatedPerson.memberProfile'])
                ->orderByDesc('is_primary')
                ->orderBy('id');
        };
    }

    protected function widowConstraint(): \Closure
    {
        return function (Builder $query) {
            $query->whereHas('relationships', function (Builder $relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Spouse->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    });
            });
        };
    }

    protected function orphanConstraint(): \Closure
    {
        return function (Builder $query) {
            $query->whereHas('relationships', function (Builder $relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Parent->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    });
            });
        };
    }

    protected function personMatchesConstraint(Person $person, \Closure $constraint): bool
    {
        return Person::query()->whereKey($person->id)->where($constraint)->exists();
    }
}
