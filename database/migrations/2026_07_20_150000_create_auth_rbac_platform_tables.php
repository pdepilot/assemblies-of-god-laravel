<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_sessions')) {
            Schema::create('admin_sessions', function (Blueprint $table) {
                $table->string('id', 128)->primary();
                $table->unsignedBigInteger('admin_id');
                $table->char('device_fingerprint', 64);
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->boolean('remember_me')->default(false);
                $table->dateTime('last_activity');
                $table->dateTime('expires_at');
                $table->dateTime('created_at')->useCurrent();

                $table->index('admin_id', 'idx_session_admin');
                $table->index('expires_at', 'idx_session_expires');
            });
        }

        if (! Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('email_attempted');
                $table->char('device_fingerprint', 64);
                $table->string('source', 40)->default('admin/login');
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->string('browser_info', 500)->nullable();
                $table->boolean('success')->default(false);
                $table->string('failure_reason', 100)->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index(['device_fingerprint', 'success', 'created_at'], 'idx_login_success');
                $table->index(['device_fingerprint', 'source', 'created_at'], 'idx_login_fp_source_created');
                $table->index('device_fingerprint', 'idx_login_fingerprint');
                $table->index('source', 'idx_login_source');
                $table->index('ip_address', 'idx_login_ip');
                $table->index('created_at', 'idx_login_created');
            });
        }

        if (! Schema::hasTable('device_bans')) {
            Schema::create('device_bans', function (Blueprint $table) {
                $table->id();
                $table->char('device_fingerprint', 64);
                $table->string('source', 40)->default('admin/login');
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->string('browser_info', 500)->nullable();
                $table->unsignedTinyInteger('ban_level')->default(1);
                $table->unsignedInteger('ban_count')->default(1);
                $table->dateTime('ban_start');
                $table->dateTime('ban_expires');
                $table->boolean('is_active')->default(true);
                $table->dateTime('lifted_at')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index(['device_fingerprint', 'is_active'], 'idx_ban_fingerprint_active');
                $table->index(['device_fingerprint', 'source', 'is_active'], 'idx_ban_fp_source_active');
                $table->index('source', 'idx_ban_source');
                $table->index('ban_expires', 'idx_ban_expires');
            });
        }

        if (! Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 80);
                $table->string('severity', 20)->default('info');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->char('device_fingerprint', 64)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index('event_type', 'idx_security_event');
                $table->index('severity', 'idx_security_severity');
                $table->index('created_at', 'idx_security_created');
            });
        }

        if (! Schema::hasTable('rate_limits')) {
            Schema::create('rate_limits', function (Blueprint $table) {
                $table->string('rate_key', 191)->primary();
                $table->unsignedInteger('hits')->default(1);
                $table->dateTime('expires_at');
            });
        }

        if (! Schema::hasTable('admin_notification_reads')) {
            Schema::create('admin_notification_reads', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id')->primary();
                $table->unsignedBigInteger('last_read_log_id')->default(0);
                $table->dateTime('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique('uk_roles_slug');
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->string('dashboard_type', 64)->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();

                $table->index('dashboard_type', 'idx_roles_dashboard_type');
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('module', 64);
                $table->string('action', 64);
                $table->string('permission_key', 128)->unique('uk_permissions_key');
                $table->string('label', 160);
                $table->text('description')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index('module', 'idx_permissions_module');
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->dateTime('granted_at')->useCurrent();

                $table->primary(['role_id', 'permission_id']);
                $table->index('permission_id', 'idx_role_permissions_permission');
            });
        }

        if (! Schema::hasTable('admin_role_assignments')) {
            Schema::create('admin_role_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->dateTime('assigned_at')->useCurrent();

                $table->primary(['admin_id', 'role_id']);
                $table->index('role_id', 'idx_admin_role_assignments_role');
            });
        }

        if (! Schema::hasTable('admin_permissions')) {
            Schema::create('admin_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->dateTime('granted_at')->useCurrent();

                $table->primary(['admin_id', 'permission_id']);
                $table->index('permission_id', 'idx_admin_permissions_permission');
            });
        }

        if (! Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique('uk_admin_roles_slug');
                $table->string('name', 120);
                $table->text('description')->nullable();
                $table->json('permissions');
                $table->boolean('is_system')->default(false);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('platform_setting_groups')) {
            Schema::create('platform_setting_groups', function (Blueprint $table) {
                $table->string('group_key', 64)->primary();
                $table->json('settings');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('platform_backups')) {
            Schema::create('platform_backups', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('backup_type', 32)->default('full');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_backups');
        Schema::dropIfExists('platform_setting_groups');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_role_assignments');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('admin_notification_reads');
        Schema::dropIfExists('rate_limits');
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('device_bans');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('admin_sessions');
    }
};
