<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ScholarshipCommitteeSeeder extends Seeder
{
    public function run(): void
    {
        $perm = Permission::firstOrCreate(['name' => 'review scholarship applications']);
        $role = Role::firstOrCreate(['name' => 'Scholarship Committee']);

        if (!$role->hasPermissionTo($perm)) {
            $role->givePermissionTo($perm);
        }
    }
}
