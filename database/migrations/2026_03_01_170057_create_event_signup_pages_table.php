<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_signup_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('org_event_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);

            $table->string('slug', 120)->unique();
            $table->string('title_override')->nullable();
            $table->longText('description')->nullable();

            $table->string('cover_image_path')->nullable();
            $table->integer('capacity')->nullable();

            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();

            $table->text('confirmation_message')->nullable();

            $table->timestamps();

            $table->index(['org_event_id','is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_signup_pages');
    }
};
