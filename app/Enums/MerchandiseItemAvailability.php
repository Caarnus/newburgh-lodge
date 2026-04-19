<?php

namespace App\Enums;

enum MerchandiseItemAvailability: string
{
    case OnHand = 'on_hand';
    case Preorder = 'preorder';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $availability) => $availability->value,
            self::cases()
        );
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        return [
            [
                'label' => 'On Hand',
                'value' => self::OnHand->value,
            ],
            [
                'label' => 'Pre-order',
                'value' => self::Preorder->value,
            ],
        ];
    }

    public static function label(string $value): string
    {
        return match ($value) {
            self::OnHand->value => 'On Hand',
            self::Preorder->value => 'Pre-order',
            default => $value,
        };
    }
}

