<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_import_batch_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('row_number');
            $table->string('row_hash',64)->nullable();
            $table->string('status')->default('new_person');
            $table->string('match_strategy')->nullable();

            $table->foreignId('matched_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('matched_member_profile_id')->nullable()->constrained('member_profiles')->nullOnDelete();

            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();

            $table->text('review_notes')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(['member_import_batch_id','row_number'], 'member_import_rows_batch_row_unique');
            $table->index(['member_import_batch_id','status']);
            $table->index(['member_import_batch_id','match_strategy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_import_rows');
    }
};
