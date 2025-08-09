<?php

namespace App\Helpers;

enum RoleEnum: string
{
    case NONE = 'None';
    case USER = 'User';
    case MEMBER = 'Member';
    case ENTERED_APPRENTICE = 'Entered Apprentice';
    case FELLOWCRAFT = 'Fellowcraft';
    case MASTER_MASON = 'Master Mason';
    case OFFICER = 'Officer';
    case SECRETARY = 'Secretary';
    case ADMIN = 'Administrator';
}
