<?php

namespace App\Helpers;

use App\Models\User;

class Utils
{
    public static function checkDegree(User $user, RoleEnum $required): bool
    {
        $has = Utils::degreeToNum($user->degree);
        $needed = Utils::degreeToNum($required);

        return $needed <= $has;
    }

    public static function degreeToNum(RoleEnum $degree): int
    {
        if ($degree == RoleEnum::MASTER_MASON) {
            return 3;
        } elseif ($degree == RoleEnum::FELLOWCRAFT) {
            return 2;
        } elseif ($degree == RoleEnum::ENTERED_APPRENTICE) {
            return 1;
        } else {
            return 0;
        }
    }
}
