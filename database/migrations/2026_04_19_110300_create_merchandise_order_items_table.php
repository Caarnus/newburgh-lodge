<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchandise_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_order_id')->constrained('merchandise_orders')->cascadeOnDelete();
            $table->foreignId('merchandise_item_id')->nullable()->constrained('merchandise_items')->nullOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('unit_price_cents')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('size', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_order_items');
    }
};

