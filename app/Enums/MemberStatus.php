<?php

namespace App\Enums;

enum MemberStatus: string
{
    case MasterMason = 'Master Mason';
    case Fellowcraft = 'Fellowcraft';
    case EnteredApprentice = 'Entered Apprentice';
    case Petitioner = 'Petitioner';
    case Honorary = 'Honorary';
    case Lost = 'Lost';
    case Demitted = 'Demitted';
    case Suspended = 'Suspended';
    case Expelled = 'Expelled';
    case Deceased = 'Deceased';

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
        return array_map(
            static fn (self $status) => [
                'label' => $status->value,
                'value' => $status->value,
            ],
            self::cases()
        );
    }
}
