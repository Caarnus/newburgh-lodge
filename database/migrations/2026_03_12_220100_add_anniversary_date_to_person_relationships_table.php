<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_relationships', function (Blueprint $table) {
            $table->date('anniversary_date')->nullable()->after('inverse_relationship_type');
        });
    }

    public function down(): void
    {
        Schema::table('person_relationships', function (Blueprint $table) {
            $table->dropColumn('anniversary_date');
        });
    }
};

