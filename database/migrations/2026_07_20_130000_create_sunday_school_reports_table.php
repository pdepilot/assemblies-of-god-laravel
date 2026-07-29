<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sunday_school_reports')) {
            return;
        }

        Schema::create('sunday_school_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 60);
            $table->unsignedBigInteger('class_id')->nullable();
            $table->date('report_date');
            $table->json('report_data')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['report_type', 'report_date'], 'idx_ss_report_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_reports');
    }
};
