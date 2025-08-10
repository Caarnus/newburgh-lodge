<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('past_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('year');
            $table->boolean('deceased');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('past_masters');
    }
};
