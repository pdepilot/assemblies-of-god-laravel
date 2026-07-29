<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sunday_school_visitors')) {
            Schema::create('sunday_school_visitors', function (Blueprint $table) {
                $table->id();
                $table->string('visitor_name', 255);
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->string('invited_by', 255)->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->date('visit_date');
                $table->enum('follow_up_status', ['pending', 'contacted', 'converted', 'closed'])->default('pending');
                $table->text('follow_up_notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('visit_date', 'idx_ss_visitor_date');
                $table->index('class_id', 'idx_ss_visitor_class');
            });
        }

        if (! Schema::hasTable('sunday_school_notifications')) {
            Schema::create('sunday_school_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('notification_type', 60);
                $table->enum('recipient_type', ['student', 'teacher', 'parent', 'class', 'all'])->default('all');
                $table->unsignedBigInteger('recipient_id')->nullable();
                $table->enum('channel', ['email', 'sms', 'whatsapp'])->default('email');
                $table->string('subject', 255);
                $table->text('body');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['status', 'created_at'], 'idx_ss_notif_status');
            });
        }

        if (! Schema::hasTable('sunday_school_awards')) {
            Schema::create('sunday_school_awards', function (Blueprint $table) {
                $table->id();
                $table->string('award_code', 80);
                $table->enum('award_category', ['student', 'teacher', 'class']);
                $table->string('award_name', 255);
                $table->enum('period_type', ['monthly', 'quarterly', 'annual', 'manual'])->default('annual');
                $table->string('period_label', 60);
                $table->enum('recipient_type', ['student', 'teacher', 'class']);
                $table->unsignedBigInteger('recipient_id');
                $table->string('recipient_name', 255);
                $table->unsignedBigInteger('class_id')->nullable();
                $table->string('class_name', 120)->nullable();
                $table->decimal('score', 8, 2)->default(0);
                $table->text('achievement_summary')->nullable();
                $table->enum('status', ['recommended', 'approved', 'published', 'rejected'])->default('recommended');
                $table->timestamp('recommended_at')->useCurrent();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();

                $table->index(['period_type', 'period_label'], 'idx_ss_award_period');
                $table->index('status', 'idx_ss_award_status');
                $table->index(['recipient_type', 'recipient_id'], 'idx_ss_award_recipient');
            });
        }

        if (! Schema::hasTable('sunday_school_promotion_evaluations')) {
            Schema::create('sunday_school_promotion_evaluations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedSmallInteger('promotion_year');
                $table->decimal('attendance_score', 5, 2)->default(0);
                $table->decimal('memory_verse_score', 5, 2)->default(0);
                $table->decimal('participation_score', 5, 2)->default(0);
                $table->decimal('punctuality_score', 5, 2)->default(0);
                $table->decimal('overall_score', 5, 2)->default(0);
                $table->boolean('meets_requirements')->default(false);
                $table->enum('recommendation', ['promote', 'repeat', 'graduate', 'review'])->default('review');
                $table->timestamp('evaluated_at')->useCurrent();
                $table->unsignedBigInteger('evaluated_by')->nullable();

                $table->unique(['student_id', 'promotion_year'], 'uk_ss_promo_eval');
                $table->index('promotion_year', 'idx_ss_promo_year');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_promotion_evaluations');
        Schema::dropIfExists('sunday_school_awards');
        Schema::dropIfExists('sunday_school_notifications');
        Schema::dropIfExists('sunday_school_visitors');
    }
};
