<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSignupPage extends Model
{
    protected $fillable = [
        'org_event_id',
        'is_enabled',
        'slug',
        'title_override',
        'description',
        'cover_image_path',
        'capacity',
        'opens_at',
        'closes_at',
        'confirmation_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(OrgEvent::class, 'org_event_id');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(EventSignup::class, 'event_signup_page_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
