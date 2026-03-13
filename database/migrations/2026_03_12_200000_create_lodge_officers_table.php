<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lodge_officers', function (Blueprint $table) {
            $table->id();
            $table->string('slot_key')->unique();
            $table->string('title');
            $table->unsignedSmallInteger('display_order');
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('lodge_officers')->insert([
            ['slot_key' => 'worshipful_master', 'title' => 'Worshipful Master', 'display_order' => 10, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'senior_warden', 'title' => 'Senior Warden', 'display_order' => 20, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'junior_warden', 'title' => 'Junior Warden', 'display_order' => 30, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'treasurer', 'title' => 'Treasurer', 'display_order' => 40, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'secretary', 'title' => 'Secretary', 'display_order' => 50, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'senior_deacon', 'title' => 'Senior Deacon', 'display_order' => 60, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'junior_deacon', 'title' => 'Junior Deacon', 'display_order' => 70, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'senior_steward', 'title' => 'Senior Steward', 'display_order' => 80, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'junior_steward', 'title' => 'Junior Steward', 'display_order' => 90, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'tyler', 'title' => 'Tyler', 'display_order' => 100, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'chaplain', 'title' => 'Chaplain', 'display_order' => 110, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'trustee_1', 'title' => 'Trustee', 'display_order' => 120, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'trustee_2', 'title' => 'Trustee', 'display_order' => 130, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['slot_key' => 'trustee_3', 'title' => 'Trustee', 'display_order' => 140, 'person_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lodge_officers');
    }
};

