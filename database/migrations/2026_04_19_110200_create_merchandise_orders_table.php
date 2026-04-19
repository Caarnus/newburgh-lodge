<?php

use App\Enums\MerchandiseItemAvailability;
use App\Enums\MerchandiseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchandise_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_type', 30)->default(MerchandiseItemAvailability::OnHand->value);
            $table->string('status', 30)->default(MerchandiseOrderStatus::Submitted->value);
            $table->string('customer_name')->nullable();
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['order_type', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_orders');
    }
};

