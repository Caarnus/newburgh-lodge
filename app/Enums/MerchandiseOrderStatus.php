<?php

namespace App\Enums;

enum MerchandiseOrderStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case PaymentReceived = 'payment_received';
    case Delivered = 'delivered';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status) => $status->value,
            self::cases()
        );
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        return [
            ['label' => 'Submitted', 'value' => self::Submitted->value],
            ['label' => 'Confirmed', 'value' => self::Confirmed->value],
            ['label' => 'Payment Received', 'value' => self::PaymentReceived->value],
            ['label' => 'Delivered', 'value' => self::Delivered->value],
        ];
    }

    public static function label(string $value): string
    {
        return match ($value) {
            self::Submitted->value => 'Submitted',
            self::Confirmed->value => 'Confirmed',
            self::PaymentReceived->value => 'Payment Received',
            self::Delivered->value => 'Delivered',
            default => $value,
        };
    }
}

