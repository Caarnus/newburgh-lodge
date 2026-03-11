<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();

            $table->string('member_number')->nullable();
            $table->string('status')->nullable();
            $table->string('member_type')->nullable();

            $table->date('ea_date')->nullable();
            $table->date('fc_date')->nullable();
            $table->date('mm_date')->nullable();
            $table->date('demit_date')->nullable();

            $table->string('roster_import_source')->nullable();
            $table->datetime('last_imported_at')->nullable();

            $table->boolean('can_auto_match_registration')->default(true);
            $table->boolean('directory_visible')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('person_id');
            $table->unique('member_number');
            $table->index('status');
            $table->index('member_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
