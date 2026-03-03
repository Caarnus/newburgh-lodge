<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'phone',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? Str::lower(trim($value)) : null;
    }

    public function signups(): HasMany
    {
        return $this->hasMany(EventSignup::class);
    }
}
