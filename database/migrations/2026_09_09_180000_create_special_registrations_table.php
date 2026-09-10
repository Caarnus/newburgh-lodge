<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('special_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('form', 80);
            $table->string('registration_type', 40)->nullable();
            $table->string('name', 160)->nullable();
            $table->string('email', 255);
            $table->string('phone', 40);
            $table->string('team_name', 160)->nullable();
            $table->json('team_members')->nullable();
            $table->timestamps();

            $table->index(['form', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_registrations');
    }
};
