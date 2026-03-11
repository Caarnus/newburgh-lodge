<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
            $first = trim((string) ($this->preferred_name ?: $this->first_name));
            $middle = trim((string) $this->middle_name);
            $last = trim((string) $this->last_name);
            $suffix = trim((string) $this->suffix);

            return collect([$first, $middle, $last, $suffix])
                ->filter(fn ($value) => $value !== '')
                ->implode(' ');
        });
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
}
