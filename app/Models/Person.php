<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'preferred_name',
        'display_name_override',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'birth_date',
        'notes',
        'is_deceased',
        'death_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_deceased' => 'boolean',
        'death_date' => 'date',
    ];

    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(PersonRelationship::class);
    }

    public function relatedTo(): HasMany
    {
        return $this->hasMany(PersonRelationship::class, 'related_person_id');
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(PersonContactLog::class)->latest('contacted_at');
    }

    public function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            $override = trim((string) ($this->display_name_override ?? ''));

            if ($override !== '') {
                return $override;
            }

            $first = trim((string) ($this->preferred_name ?: $this->first_name));
            $middle = trim((string) $this->middle_name);
            $last = trim((string) $this->last_name);
            $suffix = trim((string) $this->suffix);

            $base = collect([$first, $middle, $last, $suffix])
                ->filter(fn ($value) => $value !== '')
                ->implode(' ');

            if ($this->isPastMaster() && ! preg_match('/,\s*PM$/i', $base)) {
                return $base === '' ? 'PM' : "{$base}, PM";
            }

            return $base;
        });
    }

    protected function isPastMaster(): bool
    {
        if ($this->relationLoaded('memberProfile')) {
            return (bool) ($this->getRelation('memberProfile')?->past_master ?? false);
        }

        return (bool) ($this->memberProfile?->past_master ?? false);
    }

    public function fullName(): Attribute
    {
        return Attribute::get(function (): string {
            return collect([
                trim((string) $this->first_name),
                trim((string) $this->middle_name),
                trim((string) $this->last_name),
                trim((string) $this->suffix),
            ])
                ->filter(fn ($value) => $value !== '')
                ->implode(' ');
        });
    }

    public function primaryRelationship(string $type): ?PersonRelationship
    {
        return $this->relationships()
            ->where('relationship_type', $type)
            ->orderByDesc('is_primary')
            ->first();
    }

    public function scopeHideDeceased(Builder $query, bool $hide = true): Builder
    {
        if (! $hide) {
            return $query;
        }

        return $query
            ->where(function (Builder $statusBuilder) {
                $statusBuilder
                    ->where('people.is_deceased', false)
                    ->orWhereNull('people.is_deceased');
            })
            ->whereNull('people.death_date');
    }

    public function scopeWhereHasEmailValue(Builder $query, ?string $hasEmail): Builder
    {
        if ($hasEmail === 'yes') {
            return $query->whereRaw("TRIM(COALESCE(people.email, '')) <> ''");
        }

        if ($hasEmail === 'no') {
            return $query->where(function (Builder $builder) {
                $builder->whereNull('people.email')
                    ->orWhereRaw("TRIM(COALESCE(people.email, '')) = ''");
            });
        }

        return $query;
    }

    public function scopeWhereHasPhoneValue(Builder $query, ?string $hasPhone): Builder
    {
        if ($hasPhone === 'yes') {
            return $query->whereRaw("TRIM(COALESCE(people.phone, '')) <> ''");
        }

        if ($hasPhone === 'no') {
            return $query->where(function (Builder $builder) {
                $builder->whereNull('people.phone')
                    ->orWhereRaw("TRIM(COALESCE(people.phone, '')) = ''");
            });
        }

        return $query;
    }

    public function scopeWhereLastContactOlderThanDays(Builder $query, ?int $days): Builder
    {
        $days = (int) $days;

        if ($days <= 0) {
            return $query;
        }

        $threshold = now()->subDays($days);

        return $query->whereDoesntHave('contactLogs', function (Builder $contactQuery) use ($threshold) {
            $contactQuery->where('contacted_at', '>=', $threshold);
        });
    }
}
