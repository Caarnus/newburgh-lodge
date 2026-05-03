<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VolunteerSignupRegistrant extends Model
{
    protected $fillable = [
        'volunteer_signup_sheet_id',
        'user_id',
        'person_id',
        'name',
        'email',
    ];

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? Str::lower(trim($value)) : null;
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupSheet::class, 'volunteer_signup_sheet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VolunteerSignupAssignment::class, 'volunteer_signup_registrant_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(VolunteerSignupReminder::class, 'volunteer_signup_registrant_id');
    }
}
