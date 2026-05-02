<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('past_masters', function (Blueprint $table) {
            $table->foreignId('person_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('past_masters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
