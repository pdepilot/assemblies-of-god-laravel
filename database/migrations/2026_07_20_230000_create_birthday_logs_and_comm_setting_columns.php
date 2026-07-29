<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('birthday_logs')) {
            Schema::create('birthday_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id')->index();
                $table->date('birthday_date')->index();
                $table->string('parent_email', 255)->nullable();
                $table->boolean('email_sent')->default(false);
                $table->boolean('sms_sent')->default(false);
                $table->string('sms_phone', 40)->nullable();
                $table->string('message_subject', 500)->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['member_id', 'birthday_date'], 'uk_birthday_logs_member_date');
            });
        }

        if (Schema::hasTable('communication_settings') && ! Schema::hasColumn('communication_settings', 'birthday_auto_sms_enabled')) {
            Schema::table('communication_settings', function (Blueprint $table) {
                $table->boolean('birthday_auto_sms_enabled')->default(true)->after('birthday_auto_email_enabled');
            });
        }

        if (Schema::hasTable('email_settings') && ! Schema::hasColumn('email_settings', 'smtp_encryption')) {
            Schema::table('email_settings', function (Blueprint $table) {
                $table->string('smtp_encryption', 16)->nullable()->default('tls')->after('smtp_pass');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_logs');

        if (Schema::hasTable('communication_settings') && Schema::hasColumn('communication_settings', 'birthday_auto_sms_enabled')) {
            Schema::table('communication_settings', function (Blueprint $table) {
                $table->dropColumn('birthday_auto_sms_enabled');
            });
        }

        if (Schema::hasTable('email_settings') && Schema::hasColumn('email_settings', 'smtp_encryption')) {
            Schema::table('email_settings', function (Blueprint $table) {
                $table->dropColumn('smtp_encryption');
            });
        }
    }
};
