<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraiser_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->timestamps();
        });

        DB::table('fundraiser_categories')->insert([
            [
                'name' => 'Lodge Improvements',
                'slug' => 'lodge-improvements',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Community Support',
                'slug' => 'community-support',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('fundraisers', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('title')
                ->constrained('fundraiser_categories')
                ->restrictOnDelete();
        });

        $defaultCategoryId = DB::table('fundraiser_categories')
            ->where('slug', 'lodge-improvements')
            ->value('id');

        if ($defaultCategoryId) {
            DB::table('fundraisers')
                ->whereNull('category_id')
                ->update(['category_id' => $defaultCategoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('fundraisers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('fundraiser_categories');
    }
};

