<?php

use App\Enums\MerchandiseItemAvailability;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchandise_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('availability', 30)->default(MerchandiseItemAvailability::OnHand->value);
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('requires_size')->default(false);
            $table->json('size_options')->nullable();
            $table->boolean('is_limited_edition')->default(false);
            $table->unsignedInteger('stock_remaining')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['availability', 'is_active']);
            $table->index(['sort_order', 'name']);
        });

        DB::table('merchandise_items')->insert([
            [
                'name' => 'Polo Shirt',
                'description' => 'Embroidered lodge polo shirt.',
                'availability' => MerchandiseItemAvailability::OnHand->value,
                'price_cents' => 4500,
                'requires_size' => true,
                'size_options' => json_encode(['S', 'M', 'L', 'XL', '2XL', '3XL'], JSON_THROW_ON_ERROR),
                'is_limited_edition' => false,
                'stock_remaining' => null,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lodge Hat',
                'description' => 'Adjustable hat with lodge logo.',
                'availability' => MerchandiseItemAvailability::OnHand->value,
                'price_cents' => 2500,
                'requires_size' => false,
                'size_options' => json_encode([], JSON_THROW_ON_ERROR),
                'is_limited_edition' => false,
                'stock_remaining' => null,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Challenge Coin',
                'description' => 'Limited edition lodge challenge coin.',
                'availability' => MerchandiseItemAvailability::OnHand->value,
                'price_cents' => 1500,
                'requires_size' => false,
                'size_options' => json_encode([], JSON_THROW_ON_ERROR),
                'is_limited_edition' => true,
                'stock_remaining' => 42,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lodge Koozie',
                'description' => 'Register your interest in a future lodge koozie order.',
                'availability' => MerchandiseItemAvailability::Preorder->value,
                'price_cents' => 700,
                'requires_size' => false,
                'size_options' => json_encode([], JSON_THROW_ON_ERROR),
                'is_limited_edition' => false,
                'stock_remaining' => null,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_items');
    }
};
