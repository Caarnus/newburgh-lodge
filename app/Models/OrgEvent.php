<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgEvent extends Model
{
    protected $fillable = [
        'title',
        'type_id',
        'description',
        'start',
        'end',
        'all_day',
        'repeats',
        'rrule',
        'location',
        'is_public',
        'masons_only',
        'degree_required',
        'open_to',
    ];

    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_public' => 'boolean',
            'masons_only' => 'boolean',
            'all_day' => 'boolean',
            'repeats' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrgEventType::class, 'type_id');
    }
}
