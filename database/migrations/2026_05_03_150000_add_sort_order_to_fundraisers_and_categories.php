<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fundraiser_categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('slug');
            $table->index(['sort_order', 'name']);
        });

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('category_id');
            $table->index(['category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });

        Schema::table('fundraiser_categories', function (Blueprint $table) {
            $table->dropIndex(['sort_order', 'name']);
            $table->dropColumn('sort_order');
        });
    }
};
