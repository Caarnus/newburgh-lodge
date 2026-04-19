<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fundraiser extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_id',
        'slug',
        'short_description',
        'description',
        'goal_amount',
        'raised_amount',
        'is_active',
        'starts_at',
        'ends_at',
        'image_paths',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'category_id' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'image_paths' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FundraiserCategory::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function isPubliclyVisible(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function progressPercent(bool $capAtHundred = false): float
    {
        $goal = (float) $this->goal_amount;

        if ($goal <= 0) {
            return 0.0;
        }

        $percent = (((float) $this->raised_amount) / $goal) * 100;
        $percent = $capAtHundred ? min($percent, 100) : $percent;

        return round($percent, 1);
    }
}
