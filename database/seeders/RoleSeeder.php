<?php

namespace Database\Seeders;

use App\Helpers\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['code' => RoleEnum::ADMIN]);
        Role::firstOrCreate(['code' => RoleEnum::SECRETARY]);
        Role::firstOrCreate(['code' => RoleEnum::OFFICER]);
        Role::firstOrCreate(['code' => RoleEnum::MASTER_MASON]);
        Role::firstOrCreate(['code' => RoleEnum::FELLOWCRAFT]);
        Role::firstOrCreate(['code' => RoleEnum::ENTERED_APPRENTICE]);
        Role::firstOrCreate(['code' => RoleEnum::MEMBER]);
        Role::firstOrCreate(['code' => RoleEnum::USER]);
        Role::firstOrCreate(['code' => RoleEnum::NONE]);
    }
}
