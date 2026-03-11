<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state',50)->nullable();
            $table->string('postal_code',20)->nullable();
            $table->date('birth_date')->nullable();

            $table->text('notes')->nullable();

            $table->boolean('is_deceased')->default(false)->index();
            $table->date('death_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('last_name');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
