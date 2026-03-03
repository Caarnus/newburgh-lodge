<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_subscribers', function (Blueprint $table) {
            $table->id();

            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamps();

            $table->index(['email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_subscribers');
    }
};
