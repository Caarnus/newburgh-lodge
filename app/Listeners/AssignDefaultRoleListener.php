<?php

namespace App\Listeners;

use App\Helpers\RoleEnum;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Models\Role;

class AssignDefaultRoleListener
{
    public function __construct()
    {
    }

    public function handle($event): void
    {
        $event->user->assignRole(RoleEnum::USER->value);
    }
}
