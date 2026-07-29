<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('church_events')) {
            Schema::create('church_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_code', 20)->unique('uk_event_code');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('event_date');
                $table->time('event_time')->nullable();
                $table->string('location')->default('');
                $table->string('category', 32)->default('other');
                $table->string('image_path')->default('img/events-1.jpg');
                $table->string('recurrence_label', 120)->nullable();
                $table->string('schedule_display', 120)->nullable();
                $table->string('public_category_label', 80)->nullable();
                $table->string('icon_class', 50)->default('fa-church');
                $table->string('cta_text', 100)->default('Learn more');
                $table->string('cta_url')->default('contact');
                $table->boolean('is_recurring')->default(false);
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('expected_attendance')->default(0);
                $table->string('status', 32)->default('upcoming');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index('event_date', 'idx_event_date');
                $table->index('status', 'idx_event_status');
            });
        }

        if (! Schema::hasTable('registration_portals')) {
            Schema::create('registration_portals', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 120)->unique('uq_registration_portal_slug');
                $table->string('event_name');
                $table->string('event_subtitle')->nullable();
                $table->string('category', 80)->nullable();
                $table->mediumText('description')->nullable();
                $table->string('theme', 120)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->dateTime('registration_opens')->nullable();
                $table->dateTime('registration_closes')->nullable();
                $table->string('venue')->nullable();
                $table->string('map_link', 500)->nullable();
                $table->string('banner_path', 500)->nullable();
                $table->string('organizer')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 40)->nullable();
                $table->unsignedInteger('max_registrants')->nullable();
                $table->string('status', 20)->default('draft');
                $table->json('landing_config')->nullable();
                $table->json('registration_settings')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['status', 'start_date'], 'idx_registration_portal_status');
            });
        }

        if (! Schema::hasTable('registration_fields')) {
            Schema::create('registration_fields', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('portal_id');
                $table->string('field_key', 80);
                $table->string('field_type', 40);
                $table->string('label');
                $table->string('placeholder')->nullable();
                $table->string('help_text', 500)->nullable();
                $table->boolean('is_required')->default(false);
                $table->json('validation_rules')->nullable();
                $table->string('default_value', 500)->nullable();
                $table->string('field_width', 16)->default('full');
                $table->json('options_json')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['portal_id', 'field_key'], 'uq_portal_field_key');
                $table->index(['portal_id', 'sort_order'], 'idx_registration_fields_portal');
            });
        }

        if (! Schema::hasTable('registrants')) {
            Schema::create('registrants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('portal_id');
                $table->string('registration_number', 40)->unique('uq_registrant_number');
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('church')->nullable();
                $table->string('state_name', 80)->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('payment_status', 20)->default('free');
                $table->string('attendance_status', 32)->default('not_checked_in');
                $table->string('qr_token', 64)->unique('uq_registrant_qr_token');
                $table->string('certificate_number', 40)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->index(['portal_id', 'status', 'created_at'], 'idx_registrants_portal');
                $table->index(['portal_id', 'email'], 'idx_registrants_email');
            });
        }

        if (! Schema::hasTable('registrant_answers')) {
            Schema::create('registrant_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('registrant_id');
                $table->unsignedBigInteger('field_id')->nullable();
                $table->string('field_key', 80);
                $table->text('answer_text')->nullable();
                $table->string('answer_file', 500)->nullable();

                $table->index('registrant_id', 'idx_registrant_answers_registrant');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registrant_answers');
        Schema::dropIfExists('registrants');
        Schema::dropIfExists('registration_fields');
        Schema::dropIfExists('registration_portals');
        Schema::dropIfExists('church_events');
    }
};
