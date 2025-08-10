<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('org_events', function (Blueprint $table) {
            $table->boolean('masons_only')->default(false);
            $table->string('degree_required')->default('none');
            $table->string('open_to')->default('all');
            $table->unsignedBigInteger('type_id')->nullable()->after('title');
            $table->foreign('type_id')->references('id')->on('org_event_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('org_events', function (Blueprint $table) {
            $table->dropForeign('org_events_type_id_foreign');
            $table->dropColumn('type_id');
        });
    }
};
