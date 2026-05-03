<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerSignupReminder extends Model
{
    protected $fillable = [
        'volunteer_signup_registrant_id',
        'reminder_type',
        'occurrence_starts_at',
        'send_at',
        'reserved_at',
        'reservation_token',
        'sent_at',
        'canceled_at',
        'last_error',
    ];

    protected $casts = [
        'occurrence_starts_at' => 'datetime',
        'send_at' => 'datetime',
        'reserved_at' => 'datetime',
        'sent_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function registrant(): BelongsTo
    {
        return $this->belongsTo(VolunteerSignupRegistrant::class, 'volunteer_signup_registrant_id');
    }
}
