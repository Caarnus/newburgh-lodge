<?php

namespace Database\Seeders;

use App\Helpers\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => $guard,
            ]);
        }

        Role::firstOrCreate([
            'name' => 'Care Committee',
            'guard_name' => $guard,
        ]);
    }
}
