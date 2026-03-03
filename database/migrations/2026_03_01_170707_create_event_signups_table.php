<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_signups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_signup_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_subscriber_id')->constrained()->cascadeOnDelete();

            $table->uuid()->unique();

            $table->boolean('remind_week_before')->default(true);
            $table->boolean('remind_day_before')->default(true);
            $table->boolean('remind_hour_before')->default(true);

            $table->enum('status', ['active','canceled'])->default('active');
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();

            $table->unique(['event_signup_page_id','event_subscriber_id']);

            $table->index(['status','canceled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_signups');
    }
};
