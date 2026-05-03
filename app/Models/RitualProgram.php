<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RitualProgram extends Model
{
    protected $fillable = [
        'name',
        'points',
        'degree_group',
        'display_order',
    ];

    protected $casts = [
        'points' => 'integer',
        'display_order' => 'integer',
    ];

    public function completionRecords(): HasMany
    {
        return $this->hasMany(RitualCompletionRecord::class);
    }
}
