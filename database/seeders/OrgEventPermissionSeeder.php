<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Helpers\RoleEnum;

class OrgEventPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $perms = [
            'view event',
            'create event',
            'update event',
            'delete event',
        ];

        // Create permissions if missing
        foreach ($perms as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Ensure roles exist from your RoleEnum
        $roles = [];
        foreach (RoleEnum::cases() as $case) {
            $roles[$case->value] = Role::firstOrCreate(
                ['name' => $case->value, 'guard_name' => $guard],
                ['name' => $case->value, 'guard_name' => $guard]
            );
        }

        // Helper
        $give = function (Role $role, array $names) use ($guard) {
            $permissions = Permission::whereIn('name', $names)->where('guard_name', $guard)->get();
            $role->syncPermissions($role->permissions->pluck('name')->merge($permissions->pluck('name'))->unique()->all());
        };

        // Assignments
        // Administrator: all event permissions
        if (isset($roles[RoleEnum::ADMIN->value])) {
            $roles[RoleEnum::ADMIN->value]->givePermissionTo(Permission::where('guard_name', $guard)->pluck('name')->all());
        }

        // Secretary: view/create/update/delete
        if (isset($roles[RoleEnum::SECRETARY->value])) {
            $give($roles[RoleEnum::SECRETARY->value], [
                'view event', 'create event', 'update event', 'delete event',
            ]);
        }

        // Officer: view/create/update/
        if (isset($roles[RoleEnum::OFFICER->value])) {
            $give($roles[RoleEnum::OFFICER->value], [
                'view event', 'create event', 'update event', 'delete event',
            ]);
        }
    }
}
