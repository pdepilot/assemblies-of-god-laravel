<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_queue')) {
            return;
        }

        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('history_id')->nullable();
            $table->string('recipient', 255);
            $table->string('recipient_name', 255)->nullable();
            $table->string('subject', 500);
            $table->mediumText('body_html');
            $table->unsignedInteger('template_id')->nullable();
            $table->string('recipient_group', 80)->nullable();
            $table->string('batch_id', 64)->nullable();
            $table->unsignedInteger('sent_by')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('processed_at')->nullable();
            $table->index(['scheduled_at', 'status'], 'idx_email_queue_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queue');
    }
};
