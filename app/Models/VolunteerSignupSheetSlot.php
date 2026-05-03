<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerSignupSheetSlot extends Model
{
    protected $fillable = [
        'volunteer_signup_sheet_role_id',
        'starts_at',
        'ends_at',
        'needed_count',
        'sort_order',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupSheetRole::class, 'volunteer_signup_sheet_role_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VolunteerSignupAssignment::class, 'volunteer_signup_sheet_slot_id');
    }
}
