<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_person_link_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('current_person_id')->nullable()->constrained('people')->nullOnDelete();

            $table->string('action');
            $table->string('match_strategy')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id','created_at']);
            $table->index(['action','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_person_link_audits');
    }
};
