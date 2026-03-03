<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('org_event_occurrence_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('org_event_id')->constrained('org_events', 'id','event_occur_ovrd_org_event_foreign')->cascadeOnDelete();

            $table->dateTime('occurrence_starts_at');

            $table->dateTime('override_starts_at');
            $table->dateTime('override_ends_at')->nullable();

            $table->boolean('is_canceled')->default(false);

            $table->timestamps();

            $table->unique(['org_event_id','occurrence_starts_at'],'uniq_event_occurrence');
            $table->index(['org_event_id','override_starts_at'],'event_occur_ovrd_org_event_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_event_occurrence_overrides');
    }
};
