<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_queue')) {
            Schema::create('sms_queue', function (Blueprint $table) {
                $table->id();
                $table->string('recipient_phone', 30);
                $table->string('recipient_name', 255)->nullable();
                $table->text('message_body');
                $table->string('provider', 40)->nullable();
                $table->string('template_slug', 120)->nullable();
                $table->string('batch_id', 64)->nullable();
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->string('priority', 16)->default('normal');
                $table->dateTime('scheduled_at');
                $table->string('status', 32)->default('pending');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->text('error_message')->nullable();
                $table->text('provider_response')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->timestamps();
                $table->index(['status', 'scheduled_at'], 'idx_sms_queue_status');
            });
        }

        if (! Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('queue_id')->nullable();
                $table->string('recipient_phone', 30);
                $table->string('recipient_name', 255)->nullable();
                $table->text('message_body');
                $table->string('provider', 40)->nullable();
                $table->string('status', 32)->default('queued');
                $table->string('provider_message_id', 120)->nullable();
                $table->text('provider_response')->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->string('template_slug', 120)->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('status', 'idx_sms_logs_status');
                $table->index('created_at', 'idx_sms_logs_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_queue');
    }
};
