<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('event_signup_reminders', function (Blueprint $table) {
            $table->dateTime('reserved_at')->nullable()->after('send_at');
            $table->uuid('reservation_token')->nullable()->after('reserved_at');

            $table->index(['reserved_at', 'sent_at', 'canceled_at'], 'idx_reminders_reserved_sent');
        });
    }

    public function down(): void
    {
        Schema::table('event_signup_reminders', function (Blueprint $table) {
            $table->dropIndex('idx_reminders_reserved_sent');
            $table->dropColumn(['reserved_at', 'reservation_token']);
        });
    }
};
