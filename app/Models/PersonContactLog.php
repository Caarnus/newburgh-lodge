<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonContactLog extends Model
{
    protected $fillable = [
        'person_id',
        'contacted_at',
        'contact_type',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
