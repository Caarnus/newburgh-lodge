<?php

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        $permission = Permission::firstOrCreate([
            'name' => PeoplePermissions::MANAGE_RITUALIST_PROGRAM,
            'guard_name' => 'web',
        ]);

        foreach ([RoleEnum::OFFICER->value, RoleEnum::SECRETARY->value, RoleEnum::ADMIN->value] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        $permission = Permission::where('name', PeoplePermissions::MANAGE_RITUALIST_PROGRAM)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        foreach ([RoleEnum::OFFICER->value, RoleEnum::SECRETARY->value, RoleEnum::ADMIN->value] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }

        $permission->delete();
    }
};
