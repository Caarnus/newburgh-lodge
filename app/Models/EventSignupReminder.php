<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSignupReminder extends Model
{
    protected $fillable = [
        'event_signup_id',
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

    public function signup(): BelongsTo
    {
        return $this->belongsTo(EventSignup::class, 'event_signup_id');
    }
}
