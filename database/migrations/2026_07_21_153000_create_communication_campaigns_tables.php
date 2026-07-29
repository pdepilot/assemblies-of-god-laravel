<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recipient_groups')) {
            Schema::create('recipient_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('group_key', 80)->unique('uk_recipient_group_key');
                $table->string('name', 160);
                $table->string('description', 255)->nullable();
                $table->enum('group_type', ['dynamic', 'smart', 'static'])->default('dynamic');
                $table->json('filters_json')->nullable();
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('member_count_cache')->default(0);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('communication_campaigns')) {
            Schema::create('communication_campaigns', function (Blueprint $table) {
                $table->increments('id');
                $table->string('campaign_code', 30)->unique('uk_campaign_code');
                $table->string('name', 255);
                $table->enum('campaign_type', ['email', 'sms', 'combined', 'whatsapp', 'push'])->default('email');
                $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'archived', 'cancelled'])->default('draft');
                $table->unsignedInteger('template_id')->nullable();
                $table->string('audience_group_key', 80)->nullable();
                $table->string('subject', 500)->nullable();
                $table->mediumText('body_html')->nullable();
                $table->mediumText('body_text')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->json('stats_json')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('status', 'idx_campaign_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_campaigns');
        Schema::dropIfExists('recipient_groups');
    }
};
