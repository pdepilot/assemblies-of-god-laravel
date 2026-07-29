<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sunday_school_certificates')) {
            return;
        }

        Schema::create('sunday_school_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 40);
            $table->string('verification_code', 64);
            $table->unsignedBigInteger('award_id')->nullable();
            $table->enum('recipient_type', ['student', 'teacher', 'class']);
            $table->string('recipient_name', 255);
            $table->string('award_title', 255);
            $table->text('achievement_description')->nullable();
            $table->date('issued_date');
            $table->string('file_path', 255);
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->enum('status', ['draft', 'issued', 'revoked'])->default('issued');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('certificate_number', 'uk_ss_cert_number');
            $table->unique('verification_code', 'uk_ss_cert_verify');
            $table->index('award_id', 'idx_ss_cert_award');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunday_school_certificates');
    }
};
