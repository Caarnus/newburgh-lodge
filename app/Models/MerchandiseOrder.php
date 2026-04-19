<?php

namespace App\Models;

use App\Enums\MerchandiseItemAvailability;
use App\Enums\MerchandiseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchandiseOrder extends Model
{
    protected $fillable = [
        'user_id',
        'order_type',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'submitted_at',
        'status_updated_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MerchandiseOrderItem::class);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function statusOptions(): array
    {
        return MerchandiseOrderStatus::options();
    }

    public static function defaultStatus(): string
    {
        return MerchandiseOrderStatus::Submitted->value;
    }

    public static function defaultOrderType(): string
    {
        return MerchandiseItemAvailability::OnHand->value;
    }
}

