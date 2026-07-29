<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username', 80)->nullable();
            $table->string('password_hash');
            $table->string('full_name')->default('Administrator');
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('department', 120)->nullable();
            $table->string('position', 120)->nullable();
            $table->string('ui_theme', 24)->default('gold');
            $table->string('ui_mode', 24)->default('dark');
            $table->string('role', 64)->default('admin');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('account_status', 32)->default('active');
            $table->boolean('force_password_change')->default(false);
            $table->dateTime('locked_at')->nullable();
            $table->string('recovery_email')->nullable();
            $table->string('recovery_phone', 30)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('remember_selector', 32)->nullable();
            $table->string('remember_token_hash')->nullable();
            $table->dateTime('remember_expires_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
