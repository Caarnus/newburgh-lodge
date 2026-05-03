<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerSignupTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(VolunteerSignupTemplateRole::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
