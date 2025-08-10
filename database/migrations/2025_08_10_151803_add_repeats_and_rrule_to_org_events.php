<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('org_events', function (Blueprint $table) {
            $table->string('rrule')->nullable()->after('end');
            $table->boolean('repeats')->default(false)->after('end');
            $table->boolean('all_day')->default(false)->after('end');
        });
    }

    public function down(): void
    {
        Schema::table('org_events', function (Blueprint $table) {
            $table->dropColumn('rrule');
            $table->dropColumn('repeats');
            $table->dropColumn('all_day');
        });
    }
};
