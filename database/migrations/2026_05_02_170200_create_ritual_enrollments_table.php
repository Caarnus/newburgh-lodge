<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ritual_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->timestamps();

            $table->foreign('person_id', 'rt_enr_person_fk')
                ->references('id')
                ->on('people')
                ->cascadeOnDelete();

            $table->unique('person_id', 'rt_enr_person_uk');
        });
    }

    public function down(): void
    {
        // Defensive cleanup for out-of-order rollbacks.
        Schema::dropIfExists('ritual_records');
        Schema::dropIfExists('ritual_completion_records');
        Schema::dropIfExists('ritual_enrollments');
    }
};
