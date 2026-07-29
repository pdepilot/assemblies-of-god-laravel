<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sunday_school_attendance')) {
            Schema::create('sunday_school_attendance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->date('attendance_date');
                $table->enum('status', ['present', 'absent', 'excused'])->default('present');
                $table->enum('arrival_status', ['early', 'on_time', 'late', 'unknown'])->default('unknown');
                $table->time('arrival_time')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'attendance_date'], 'uk_ss_attendance_student_date');
                $table->index('attendance_date', 'idx_ss_attendance_date');
                $table->index(['class_id', 'attendance_date'], 'idx_ss_attendance_class');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_attendance');
    }
};
