<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Helpers\RoleEnum;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        app(PermissionRegistrar::class)->forgetCachedPermissions(); // recommended :contentReference[oaicite:2]{index=2}

        $permissions = [
            'view newsletter',
            'create newsletter',
            'update newsletter',
            'delete newsletter',
            'view event',
            'create event',
            'update event',
            'delete event',
            'manage-content',
            'manage-gallery',
            'view member photos',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Ensure roles exist (guarded)
        $roles = [];
        foreach (RoleEnum::cases() as $case) {
            $roles[$case->value] = Role::firstOrCreate([
                'name' => $case->value,
                'guard_name' => $guard,
            ]);
        }

        // Exact mappings (based on what you posted)
        $roles[RoleEnum::MEMBER->value]?->syncPermissions([
            'view newsletter',
            'view event',
            'view member photos',
        ]);

        // Exact mappings (based on what you posted)
        $roles[RoleEnum::OFFICER->value]?->syncPermissions([
            'view newsletter','create newsletter','update newsletter',
            'view event','create event','update event','delete event',
            'manage-content',
            'manage-gallery',
        ]);

        $roles[RoleEnum::SECRETARY->value]?->syncPermissions([
            'view newsletter','create newsletter','update newsletter','delete newsletter',
            'view event','create event','update event','delete event',
            'manage-content',
            'manage-gallery',
        ]);

        // If you truly want Admin to always have *everything*:
        $roles[RoleEnum::ADMIN->value]?->syncPermissions(
            Permission::where('guard_name', $guard)->pluck('name')->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
