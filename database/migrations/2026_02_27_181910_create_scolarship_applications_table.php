<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();

            // Cycle year, limit one per period
            $table->unsignedSmallInteger('cycle_year');

            // Applicant
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state',2)->default('IN');
            $table->string('zip',10);
            $table->string('residency_duration')->nullable();
            $table->boolean('is_warrick_resident')->default(true);
            $table->integer('siblings');

            // Education
            $table->string('current_school')->nullable();
            $table->string('education_level')->nullable();
            $table->string('current_year')->nullable();
            $table->date('expected_graduation')->nullable();
            $table->string('planned_program')->nullable();
            $table->string('gpa')->nullable();
            $table->string('gpa_scale')->nullable();

            // Criteria
            $table->text('activities')->nullable();
            $table->text('awards')->nullable();
            $table->string('reason',1000)->nullable();
            $table->string('lodge_relationship')->nullable();
            $table->string('lodge_relationship_detail')->nullable();
            $table->json('attachments')->nullable();

            // Email verification
            $table->string('email_verification_token',64)->nullable()->index();
            $table->timestamp('email_verification_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // Review workflow
            $table->string('status');
            $table->timestamp('submitted_at')->nullable();

            // Auditing
            $table->string('ip_address',45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('content_hash',64)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cycle_year', 'email']);
            $table->index(['cycle_year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
