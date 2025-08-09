<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 100)->nullable();
            $table->string('actor_guard', 50)->nullable();

            $table->string('action', 150)->nullable();

            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('secondary_subject_type', 150)->nullable();
            $table->unsignedBigInteger('secondary_subject_id')->nullable();

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('meta')->nullable();

            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id')->nullable();

            $table->boolean('succeeded');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['action']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['secondary_subject_type', 'secondary_subject_id']);
            $table->index(['actor_id']);
            $table->index(['request_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
