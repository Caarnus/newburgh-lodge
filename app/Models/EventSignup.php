<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventSignup extends Model
{
    protected $fillable = [
        'event_signup_page_id',
        'event_subscriber_id',
        'uuid',
        'remind_week_before',
        'remind_day_before',
        'remind_hour_before',
        'status',
        'canceled_at',
    ];

    protected $casts = [
        'remind_week_before' => 'boolean',
        'remind_day_before' => 'boolean',
        'remind_hour_before' => 'boolean',
        'canceled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $signup) {
            if (empty($signup->uuid)) {
                $signup->uuid = (string) Str::uuid();
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(EventSignupPage::class, 'event_signup_page_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(EventSubscriber::class, 'event_subscriber_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(EventSignupReminder::class, 'event_signup_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
