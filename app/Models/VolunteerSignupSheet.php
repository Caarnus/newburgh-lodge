<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VolunteerSignupSheet extends Model
{
    protected $fillable = [
        'org_event_id',
        'volunteer_signup_template_id',
        'is_enabled',
        'slug',
        'title_override',
        'description',
        'opens_at',
        'closes_at',
        'remind_week_before',
        'remind_day_before',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'remind_week_before' => 'boolean',
        'remind_day_before' => 'boolean',
    ];

    public function setSlugAttribute(?string $value): void
    {
        $this->attributes['slug'] = $value ? Str::lower(trim($value)) : null;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(OrgEvent::class, 'org_event_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupTemplate::class, 'volunteer_signup_template_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(VolunteerSignupSheetRole::class, 'volunteer_signup_sheet_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function registrants(): HasMany
    {
        return $this->hasMany(VolunteerSignupRegistrant::class, 'volunteer_signup_sheet_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
