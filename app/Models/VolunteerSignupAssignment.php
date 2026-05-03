<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerSignupAssignment extends Model
{
    protected $fillable = [
        'volunteer_signup_registrant_id',
        'volunteer_signup_sheet_slot_id',
        'status',
        'canceled_at',
    ];

    protected $casts = [
        'canceled_at' => 'datetime',
    ];

    public function registrant(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupRegistrant::class, 'volunteer_signup_registrant_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupSheetSlot::class, 'volunteer_signup_sheet_slot_id');
    }
}
