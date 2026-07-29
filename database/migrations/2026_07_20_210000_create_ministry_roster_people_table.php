<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ministry_roster_people')) {
            Schema::create('ministry_roster_people', function (Blueprint $table) {
                $table->id();
                $table->string('ministry_key', 40);
                $table->string('person_code', 40);
                $table->string('full_name', 160);
                $table->string('first_name', 80)->nullable();
                $table->string('last_name', 80)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('email', 160)->nullable();
                $table->string('address_line1', 255)->nullable();
                $table->string('city', 80)->nullable();
                $table->string('state', 80)->nullable();
                $table->string('parent_name', 160)->nullable();
                $table->string('parent_phone', 40)->nullable();
                $table->string('parent_email', 160)->nullable();
                $table->string('group_name', 80)->nullable();
                $table->string('role_note', 120)->nullable();
                $table->string('status', 20)->default('active');
                $table->date('joined_date')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('linked_member_id')->nullable();
                $table->timestamps();

                $table->unique(['ministry_key', 'person_code'], 'uk_ministry_roster_code');
                $table->index(['ministry_key', 'status'], 'idx_ministry_roster_key_status');
                $table->index('full_name', 'idx_ministry_roster_name');
            });
        }

        if (! Schema::hasTable('ministry_roster_attendance')) {
            Schema::create('ministry_roster_attendance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('roster_person_id');
                $table->string('ministry_key', 40);
                $table->date('service_date');
                $table->string('service_type', 50)->default('weekly');
                $table->boolean('present')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->unique(
                    ['roster_person_id', 'ministry_key', 'service_date', 'service_type'],
                    'uk_ministry_roster_attendance'
                );
                $table->index('service_date', 'idx_ministry_roster_att_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_roster_attendance');
        Schema::dropIfExists('ministry_roster_people');
    }
};
