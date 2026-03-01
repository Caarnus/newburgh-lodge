<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scholarship_application_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained('scholarship_applications', 'id','sch_review_sch_app_id_foreign')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 4, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('scholarship_application_id', 'user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_application_reviews');
    }
};
