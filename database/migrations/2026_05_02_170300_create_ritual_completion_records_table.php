<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ritual_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ritual_enrollment_id');
            $table->unsignedBigInteger('ritual_program_id');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('ritual_enrollment_id', 'rt_rec_enr_fk')
                ->references('id')
                ->on('ritual_enrollments')
                ->cascadeOnDelete();
            $table->foreign('ritual_program_id', 'rt_rec_prog_fk')
                ->references('id')
                ->on('ritual_programs')
                ->cascadeOnDelete();

            $table->unique(['ritual_enrollment_id', 'ritual_program_id'], 'rt_rec_enr_prog_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ritual_records');
    }
};
