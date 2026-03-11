<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('person_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('related_person_id')->constrained('people')->cascadeOnDelete();

            $table->string('relationship_type');
            $table->string('inverse_relationship_type')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'relationship_type'], 'person_relationships_unique_pair');
            $table->index(['person_id', 'relationship_type']);
            $table->index(['related_person_id', 'relationship_type']);
        });

        DB::statement('ALTER TABLE person_relationships ADD CONSTRAINT chk_person_relationships_not_self CHECK (person_id <> related_person_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('person_relationships');
    }
};
