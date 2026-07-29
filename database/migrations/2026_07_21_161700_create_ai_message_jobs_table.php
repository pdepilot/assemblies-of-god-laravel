<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_message_jobs')) {
            return;
        }

        Schema::create('ai_message_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type', 80);
            $table->text('prompt');
            $table->json('context_json')->nullable();
            $table->mediumText('result_text')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('provider', 40)->default('future');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_message_jobs');
    }
};
