<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sunday_school_lessons')) {
            return;
        }

        Schema::create('sunday_school_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('lesson_title', 255);
            $table->date('lesson_date');
            $table->string('bible_text', 255)->nullable();
            $table->string('golden_text', 255)->nullable();
            $table->text('objectives')->nullable();
            $table->text('teaching_notes')->nullable();
            $table->text('activities')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('lesson_date', 'idx_ss_lesson_date');
            $table->index(['class_id', 'lesson_date'], 'idx_ss_lesson_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_lessons');
    }
};
