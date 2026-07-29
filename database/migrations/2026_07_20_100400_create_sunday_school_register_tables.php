<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sunday_school_offerings')) {
            Schema::create('sunday_school_offerings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->date('offering_date');
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('offering_date', 'idx_ss_offering_date');
                $table->index(['class_id', 'offering_date'], 'idx_ss_offering_class');
                $table->index(['student_id', 'offering_date'], 'idx_ss_offering_student');
            });
        }

        if (! Schema::hasTable('sunday_school_memory_verses')) {
            Schema::create('sunday_school_memory_verses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->string('verse_reference', 120);
                $table->date('recitation_date');
                $table->enum('score', ['excellent', 'passed', 'attempted', 'failed', 'not_recited'])->default('not_recited');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('recitation_date', 'idx_ss_mv_date');
                $table->index(['class_id', 'recitation_date'], 'idx_ss_mv_class');
                $table->index(['student_id', 'recitation_date'], 'idx_ss_mv_student');
            });
        }

        if (! Schema::hasTable('sunday_school_attendance_removals')) {
            Schema::create('sunday_school_attendance_removals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attendance_id')->nullable();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->string('student_name', 255);
                $table->string('student_code', 64)->nullable();
                $table->string('class_name', 255);
                $table->date('attendance_date');
                $table->enum('status', ['present', 'absent', 'excused'])->nullable();
                $table->enum('arrival_status', ['early', 'on_time', 'late', 'unknown'])->nullable();
                $table->decimal('offering_amount', 12, 2)->default(0);
                $table->boolean('memory_verse_passed')->default(false);
                $table->text('reason');
                $table->unsignedBigInteger('removed_by');
                $table->unsignedBigInteger('teacher_id')->nullable();
                $table->timestamp('removed_at')->useCurrent();
                $table->index('attendance_date', 'idx_ss_removal_date');
                $table->index(['class_id', 'attendance_date'], 'idx_ss_removal_class');
                $table->index('removed_at', 'idx_ss_removal_removed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_attendance_removals');
        Schema::dropIfExists('sunday_school_memory_verses');
        Schema::dropIfExists('sunday_school_offerings');
    }
};
