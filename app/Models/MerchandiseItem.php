<?php

namespace App\Models;

use App\Enums\MerchandiseItemAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchandiseItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'availability',
        'price_cents',
        'requires_size',
        'size_options',
        'is_limited_edition',
        'stock_remaining',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'size_options' => 'array',
        'requires_size' => 'boolean',
        'is_limited_edition' => 'boolean',
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'stock_remaining' => 'integer',
        'sort_order' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(MerchandiseOrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOnHand(Builder $query): Builder
    {
        return $query->where('availability', MerchandiseItemAvailability::OnHand->value);
    }

    public function scopePreorder(Builder $query): Builder
    {
        return $query->where('availability', MerchandiseItemAvailability::Preorder->value);
    }
}

