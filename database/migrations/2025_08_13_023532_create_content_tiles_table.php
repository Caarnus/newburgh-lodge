<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_tiles', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('welcome');
            $table->string('type');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('col_start')->default(1);
            $table->unsignedInteger('row_start')->default(1);
            $table->unsignedInteger('col_span')->default(1);
            $table->unsignedInteger('row_span')->default(1);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_tiles');
    }
};
