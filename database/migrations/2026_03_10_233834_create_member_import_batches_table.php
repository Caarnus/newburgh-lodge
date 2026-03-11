<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('import_type')->default('roster');
            $table->string('source_label')->nullable();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status')->default('uploaded');

            $table->json('summary')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('applied_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_message')->nullable();

            $table->timestamps();

            $table->index(['import_type','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_import_batches');
    }
};
