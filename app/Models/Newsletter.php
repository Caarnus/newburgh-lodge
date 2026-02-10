<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'issue',
        'summary',
        'body',
        'is_public',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }
}
