<?php

namespace App\Services\People\Directory;

use App\Enums\RelationshipType;
use App\Models\MemberProfile;
use App\Models\Person;
use App\Models\PersonContactLog;
use App\Models\PersonRelationship;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PeopleDirectoryService
{
    public function paginateMembers(array $filters): LengthAwarePaginator
    {
        $query = Person::query()
            ->select('people.*')
            ->leftJoin('member_profiles', 'member_profiles.person_id', '=', 'people.id')
            ->whereNotNull('member_profiles.id')
            ->with('memberProfile')
            ->selectSub($this->latestContactSubquery(), 'last_contact_at');

        $this->applyCommonSearch($query, $filters['q'] ?? null, includeMemberNumber: true);

        if (filled($filters['status'] ?? null)) {
            $query->where('member_profiles.status', $filters['status']);
        }

        if (filled($filters['member_type'] ?? null)) {
            $query->where('member_profiles.member_type', $filters['member_type']);
        }

        if (($filters['hide_deceased'] ?? false) === true) {
            $query->where(function (Builder $builder) {
                $builder->where('people.is_deceased', false)
                    ->orWhereNull('people.is_deceased');
            });
        }

        $this->applyMemberSort($query, $filters['sort'] ?? 'name');

        return $query->paginate($this->perPage($filters))->withQueryString();
    }

    public function paginateWidows(array $filters): LengthAwarePaginator
    {
        $query = Person::query()
            ->select('people.*')
            ->with(['relationships' => function ($relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Spouse->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    })
                    ->with(['relatedPerson.memberProfile'])
                    ->orderByDesc('is_primary')
                    ->orderBy('id');
            }])
            ->selectSub($this->latestContactSubquery(), 'last_contact_at')
            ->selectSub($this->relatedDeceasedDeathDateSubquery(RelationshipType::Spouse), 'related_member_death_date')
            ->whereHas('relationships', function (Builder $relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Spouse->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    });
            });

        $this->applyCommonSearch($query, $filters['q'] ?? null);

        if (($filters['hide_deceased'] ?? false) === true) {
            $query->where(function (Builder $builder) {
                $builder->where('people.is_deceased', false)
                    ->orWhereNull('people.is_deceased');
            });
        }

        $this->applyCareSort($query, $filters['sort'] ?? 'name');

        return $query->paginate($this->perPage($filters))->withQueryString();
    }

    public function paginateOrphans(array $filters): LengthAwarePaginator
    {
        $query = Person::query()
            ->select('people.*')
            ->with(['relationships' => function ($relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Parent->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    })
                    ->with(['relatedPerson.memberProfile'])
                    ->orderByDesc('is_primary')
                    ->orderBy('id');
            }])
            ->selectSub($this->latestContactSubquery(), 'last_contact_at')
            ->selectSub($this->relatedDeceasedDeathDateSubquery(RelationshipType::Parent), 'related_member_death_date')
            ->whereHas('relationships', function (Builder $relationshipQuery) {
                $relationshipQuery
                    ->where('relationship_type', RelationshipType::Parent->value)
                    ->whereHas('relatedPerson', function (Builder $relatedQuery) {
                        $relatedQuery
                            ->where('is_deceased', true)
                            ->whereHas('memberProfile');
                    });
            });

        $this->applyCommonSearch($query, $filters['q'] ?? null);

        if (($filters['hide_deceased'] ?? false) === true) {
            $query->where(function (Builder $builder) {
                $builder->where('people.is_deceased', false)
                    ->orWhereNull('people.is_deceased');
            });
        }

        $this->applyCareSort($query, $filters['sort'] ?? 'name');

        return $query->paginate($this->perPage($filters))->withQueryString();
    }

    public function memberStatusOptions(): array
    {
        return MemberProfile::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->map(fn (string $status) => ['label' => $status, 'value' => $status])
            ->values()
            ->all();
    }

    public function memberTypeOptions(): array
    {
        return MemberProfile::query()
            ->whereNotNull('member_type')
            ->where('member_type', '!=', '')
            ->distinct()
            ->orderBy('member_type')
            ->pluck('member_type')
            ->map(fn (string $memberType) => ['label' => $memberType, 'value' => $memberType])
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

    protected function perPage(array $filters): int
    {
        return max(10, min(100, (int) ($filters['per_page'] ?? 25)));
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
                ->orWhereRaw('LOWER(people.email) like ?', ["%{$lowerTerm}%"])
                ->orWhere('people.phone', 'like', "%{$term}%")
                ->orWhereRaw("LOWER(CONCAT(COALESCE(people.first_name, ''), ' ', COALESCE(people.last_name, ''))) like ?", ["%{$lowerTerm}%"]);

            if ($includeMemberNumber) {
                $builder->orWhere('member_profiles.member_number', 'like', "%{$term}%");
            }
        });
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

    protected function latestContactSubquery(): Builder
    {
        return PersonContactLog::query()
            ->selectRaw('MAX(contacted_at)')
            ->whereColumn('person_contact_logs.person_id', 'people.id');
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
}
