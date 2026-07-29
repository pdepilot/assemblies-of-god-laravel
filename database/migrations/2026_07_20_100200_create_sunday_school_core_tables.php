<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration compatibility:
        // - If the production legacy tables already exist, skip creating them.
        // - This allows local dev/test to run migrations safely.

        if (!Schema::hasTable('sunday_school_classes')) {
            Schema::create('sunday_school_classes', function (Blueprint $table) {
                $table->id();
                $table->string('class_code', 20)->unique('uk_ss_class_code');
                $table->string('class_name', 120);
                $table->string('age_range', 60)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->unsignedBigInteger('assistant_teacher_id')->nullable();
                $table->unsignedInteger('max_capacity')->default(50);
                $table->enum('status', ['active', 'archived'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sunday_school_teachers')) {
            Schema::create('sunday_school_teachers', function (Blueprint $table) {
                $table->id();
                $table->string('teacher_code', 20)->unique('uk_ss_teacher_code');
                $table->string('full_name', 255);
                $table->string('photo_path', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->enum('gender', ['male', 'female', 'unspecified'])->default('unspecified');
                $table->date('date_joined')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->string('qualification', 255)->nullable();
                $table->string('ministry_position', 120)->nullable();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->enum('status', ['active', 'suspended'])->default('active');
                $table->enum('membership_status', ['visitor', 'member', 'regular', 'full_member', 'baptized', 'unbaptized'])->default('unbaptized');
                $table->unsignedBigInteger('member_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sunday_school_students')) {
            Schema::create('sunday_school_students', function (Blueprint $table) {
                $table->id();
                $table->string('student_code', 20)->unique('uk_ss_student_code');
                $table->string('full_name', 255);
                $table->string('photo_path', 255)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('gender', ['male', 'female', 'unspecified'])->default('unspecified');
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'separated', 'unspecified'])->default('unspecified');
                $table->date('wedding_date')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('parent_name', 255)->nullable();
                $table->string('parent_phone', 30)->nullable();
                $table->string('parent_email', 255)->nullable();
                $table->text('address')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->string('department', 100)->default('Sunday School');
                $table->date('date_joined')->nullable();
                $table->enum('baptism_status', ['not_baptized', 'baptized', 'unknown'])->default('unknown');
                $table->enum('membership_status', ['visitor', 'member', 'regular', 'full_member', 'baptized', 'unbaptized'])->default('unbaptized');
                $table->enum('status', ['active', 'archived', 'graduated'])->default('active');
                $table->unsignedBigInteger('member_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sunday_school_parents')) {
            Schema::create('sunday_school_parents', function (Blueprint $table) {
                $table->id();
                $table->string('full_name', 255);
                $table->string('email', 255)->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sunday_school_promotions')) {
            Schema::create('sunday_school_promotions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('from_class_id')->nullable();
                $table->unsignedBigInteger('to_class_id')->nullable();
                $table->enum('promotion_type', ['promote', 'transfer', 'graduate'])->default('promote');
                $table->unsignedSmallInteger('promotion_year');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('promoted_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_promotions');
        Schema::dropIfExists('sunday_school_parents');
        Schema::dropIfExists('sunday_school_students');
        Schema::dropIfExists('sunday_school_teachers');
        Schema::dropIfExists('sunday_school_classes');
    }
};

