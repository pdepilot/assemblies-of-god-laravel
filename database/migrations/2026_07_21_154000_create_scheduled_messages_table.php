<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduled_messages')) {
            return;
        }

        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_code', 40)->unique('uk_scheduled_msg_code');
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'push', 'in_app', 'multi'])->default('email');
            $table->string('subject', 500)->nullable();
            $table->mediumText('body_html')->nullable();
            $table->mediumText('body_text')->nullable();
            $table->string('template_slug', 120)->nullable();
            $table->string('recipient_group_key', 80)->nullable();
            $table->json('recipients_json')->nullable();
            $table->enum('priority', ['high', 'normal', 'low'])->default('normal');
            $table->enum('recurrence', ['none', 'daily', 'weekly', 'monthly', 'yearly'])->default('none');
            $table->dateTime('scheduled_at');
            $table->dateTime('next_run_at')->nullable();
            $table->enum('status', ['scheduled', 'processing', 'sent', 'cancelled', 'failed'])->default('scheduled');
            $table->unsignedInteger('campaign_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_run_at'], 'idx_scheduled_next');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
    }
};
