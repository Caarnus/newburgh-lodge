<?php

use App\Helpers\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        if (!class_exists(Permission::class)) {
            return;
        }

        $Permission = Permission::class;
        $Role = Role::class;

        $perm = $Permission::firstOrCreate(['name' => 'manage-content', 'guard_name' => 'web']);

        foreach ([RoleEnum::OFFICER, RoleEnum::SECRETARY, RoleEnum::ADMIN] as $roleName) {
            $role = $Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    public function down(): void
    {
        if (!class_exists(Permission::class)) {
            return;
        }

        $Permission = Permission::class;
        $Role = Role::class;

        $perm = $Permission::where('name','manage-content')->where('guard_name','web')->first();

        foreach ([RoleEnum::OFFICER, RoleEnum::SECRETARY, RoleEnum::ADMIN] as $roleName) {
            $role = $Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && $role->hasPermissionTo($perm)) {
                $role->revokePermissionTo($perm);
            }
        }
        $perm->delete();
    }
};
