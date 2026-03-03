<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgEventOccurrenceOverride extends Model
{
    protected $fillable = [
        'org_event_id',
        'occurrence_starts_at',
        'override_starts_at',
        'override_ends_at',
        'is_canceled',
    ];

    protected $casts = [
        'occurrence_starts_at' => 'datetime',
        'override_starts_at' => 'datetime',
        'override_ends_at' => 'datetime',
        'is_canceled' => 'boolean',
    ];

    public function orgEvent(): BelongsTo
    {
        return $this->belongsTo(OrgEvent::class);
    }
}
