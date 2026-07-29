<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('automation_rules')) {
            return;
        }

        Schema::create('automation_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('rule_key', 100)->unique('uk_automation_rule_key');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('trigger_event', 100);
            $table->json('conditions_json')->nullable();
            $table->json('actions_json');
            $table->enum('channel', ['email', 'sms', 'multi', 'in_app'])->default('email');
            $table->string('template_slug', 120)->nullable();
            $table->smallInteger('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_system')->default(false);
            $table->dateTime('last_run_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['trigger_event', 'is_enabled'], 'idx_automation_trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
