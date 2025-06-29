<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jeopardy_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('answer')->nullable();
            $table->string('category');
            $table->integer('difficulty');
            $table->string('degree')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeopardy_questions');
    }
};
