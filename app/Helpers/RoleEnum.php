<?php

namespace App\Helpers;

enum RoleEnum: string
{
    case NONE = 'none';
    case MEMBER = 'mb';
    case ENTERED_APPRENTICE = 'ea';
    case FELLOWCRAFT = 'fc';
    case MASTER_MASON = 'mm';
    case OFFICER = 'of';
    case SECRETARY = 'sc';
    case ADMIN = 'ad';
}
