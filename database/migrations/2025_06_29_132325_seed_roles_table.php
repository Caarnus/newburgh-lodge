<?php

use App\Helpers\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('roles')->insert([
            ['name' => 'Member', 'code' => RoleEnum::MEMBER],
            ['name' => 'Entered Apprentice', 'code' => RoleEnum::ENTERED_APPRENTICE],
            ['name' => 'Fellowcraft', 'code' => RoleEnum::FELLOWCRAFT],
            ['name' => 'Master Mason', 'code' => RoleEnum::MASTER_MASON],
            ['name' => 'Officer', 'code' => RoleEnum::OFFICER],
            ['name' => 'Secretary', 'code' => RoleEnum::SECRETARY],
            ['name' => 'Admin', 'code' => RoleEnum::ADMIN],
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('code', RoleEnum::MEMBER)->delete();
        DB::table('roles')->where('code', RoleEnum::ENTERED_APPRENTICE)->delete();
        DB::table('roles')->where('code', RoleEnum::FELLOWCRAFT)->delete();
        DB::table('roles')->where('code', RoleEnum::MASTER_MASON)->delete();
        DB::table('roles')->where('code', RoleEnum::OFFICER)->delete();
        DB::table('roles')->where('code', RoleEnum::SECRETARY)->delete();
        DB::table('roles')->where('code', RoleEnum::ADMIN)->delete();
    }
};
