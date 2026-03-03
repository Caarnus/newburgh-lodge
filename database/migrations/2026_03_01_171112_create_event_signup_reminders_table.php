<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_signup_reminders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_signup_id')->constrained()->cascadeOnDelete();

            $table->enum('reminder_type',['week','day','hour']);

            $table->dateTime('occurrence_starts_at');
            $table->dateTime('send_at');

            $table->dateTime('sent_at')->nullable();
            $table->dateTime('canceled_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(['event_signup_id','reminder_type','occurrence_starts_at'], 'uniq_signup_type_occurrence');

            $table->index(['send_at','sent_at','canceled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_signup_reminders');
    }
};
