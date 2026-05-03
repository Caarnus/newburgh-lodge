<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('volunteer_signup_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('created_by_user_id', 'vst_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['is_active', 'sort_order'], 'vst_active_sort_idx');
        });

        Schema::create('volunteer_signup_template_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_template_id');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('volunteer_signup_template_id', 'vstr_tpl_fk')
                ->references('id')
                ->on('volunteer_signup_templates')
                ->cascadeOnDelete();

            $table->index(['volunteer_signup_template_id', 'sort_order'], 'vstr_tpl_sort_idx');
        });

        Schema::create('volunteer_signup_template_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_template_role_id');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('needed_count')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('volunteer_signup_template_role_id', 'vsts_role_fk')
                ->references('id')
                ->on('volunteer_signup_template_roles')
                ->cascadeOnDelete();

            $table->index(['volunteer_signup_template_role_id', 'sort_order'], 'vsts_role_sort_idx');
        });

        Schema::create('volunteer_signup_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('org_event_id');
            $table->unsignedBigInteger('volunteer_signup_template_id')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('slug', 120)->nullable();
            $table->string('title_override')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->boolean('remind_week_before')->default(true);
            $table->boolean('remind_day_before')->default(true);
            $table->timestamps();

            $table->foreign('org_event_id', 'vss_event_fk')
                ->references('id')
                ->on('org_events')
                ->cascadeOnDelete();

            $table->foreign('volunteer_signup_template_id', 'vss_tpl_fk')
                ->references('id')
                ->on('volunteer_signup_templates')
                ->nullOnDelete();

            $table->unique('org_event_id', 'vss_event_unq');
            $table->unique('slug', 'vss_slug_unq');
            $table->index(['is_enabled', 'opens_at', 'closes_at'], 'vss_enabled_window_idx');
        });

        Schema::create('volunteer_signup_sheet_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_sheet_id');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('volunteer_signup_sheet_id', 'vssr_sheet_fk')
                ->references('id')
                ->on('volunteer_signup_sheets')
                ->cascadeOnDelete();

            $table->index(['volunteer_signup_sheet_id', 'sort_order'], 'vssr_sheet_sort_idx');
        });

        Schema::create('volunteer_signup_sheet_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_sheet_role_id');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('needed_count')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('volunteer_signup_sheet_role_id', 'vsss_role_fk')
                ->references('id')
                ->on('volunteer_signup_sheet_roles')
                ->cascadeOnDelete();

            $table->index(['volunteer_signup_sheet_role_id', 'sort_order'], 'vsss_role_sort_idx');
        });

        Schema::create('volunteer_signup_registrants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_sheet_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('name', 160)->nullable();
            $table->string('email', 255);
            $table->timestamps();

            $table->foreign('volunteer_signup_sheet_id', 'vsreg_sheet_fk')
                ->references('id')
                ->on('volunteer_signup_sheets')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'vsreg_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('person_id', 'vsreg_person_fk')
                ->references('id')
                ->on('people')
                ->nullOnDelete();

            $table->unique(['volunteer_signup_sheet_id', 'email'], 'vsreg_sheet_email_unq');
            $table->index('person_id', 'vsreg_person_idx');
        });

        Schema::create('volunteer_signup_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_registrant_id');
            $table->unsignedBigInteger('volunteer_signup_sheet_slot_id');
            $table->enum('status', ['active', 'canceled'])->default('active');
            $table->dateTime('canceled_at')->nullable();
            $table->timestamps();

            $table->foreign('volunteer_signup_registrant_id', 'vsa_reg_fk')
                ->references('id')
                ->on('volunteer_signup_registrants')
                ->cascadeOnDelete();
            $table->foreign('volunteer_signup_sheet_slot_id', 'vsa_slot_fk')
                ->references('id')
                ->on('volunteer_signup_sheet_slots')
                ->cascadeOnDelete();

            $table->unique(['volunteer_signup_registrant_id', 'volunteer_signup_sheet_slot_id'], 'vsa_reg_slot_unq');
            $table->index(['volunteer_signup_sheet_slot_id', 'status'], 'vsa_slot_status_idx');
        });

        Schema::create('volunteer_signup_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_signup_registrant_id');
            $table->enum('reminder_type', ['week', 'day']);
            $table->dateTime('occurrence_starts_at');
            $table->dateTime('send_at');
            $table->dateTime('reserved_at')->nullable();
            $table->uuid('reservation_token')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('volunteer_signup_registrant_id', 'vsrem_reg_fk')
                ->references('id')
                ->on('volunteer_signup_registrants')
                ->cascadeOnDelete();

            $table->unique(
                ['volunteer_signup_registrant_id', 'reminder_type', 'occurrence_starts_at'],
                'vsrem_reg_type_occ_unq'
            );
            $table->index(['send_at', 'sent_at', 'canceled_at'], 'vsrem_send_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_signup_reminders');
        Schema::dropIfExists('volunteer_signup_assignments');
        Schema::dropIfExists('volunteer_signup_registrants');
        Schema::dropIfExists('volunteer_signup_sheet_slots');
        Schema::dropIfExists('volunteer_signup_sheet_roles');
        Schema::dropIfExists('volunteer_signup_sheets');
        Schema::dropIfExists('volunteer_signup_template_slots');
        Schema::dropIfExists('volunteer_signup_template_roles');
        Schema::dropIfExists('volunteer_signup_templates');
    }
};
