<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('person_contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();

            $table->dateTime('contacted_at');
            $table->string('contact_type')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['person_id','contacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_contact_logs');
    }
};
