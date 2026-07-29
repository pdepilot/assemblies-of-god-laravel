<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_bans') && ! Schema::hasColumn('device_bans', 'source')) {
            Schema::table('device_bans', function (Blueprint $table) {
                $table->string('source', 40)->default('admin/login')->after('device_fingerprint');
                $table->index(['device_fingerprint', 'source', 'is_active'], 'idx_ban_fp_source_active');
                $table->index('source', 'idx_ban_source');
            });

            DB::table('device_bans')->whereNull('source')->orWhere('source', '')->update([
                'source' => 'admin/login',
            ]);
        }

        if (Schema::hasTable('login_attempts') && ! Schema::hasColumn('login_attempts', 'source')) {
            Schema::table('login_attempts', function (Blueprint $table) {
                $table->string('source', 40)->default('admin/login')->after('device_fingerprint');
                $table->index(['device_fingerprint', 'source', 'created_at'], 'idx_login_fp_source_created');
                $table->index('source', 'idx_login_source');
            });

            DB::table('login_attempts')->whereNull('source')->orWhere('source', '')->update([
                'source' => 'admin/login',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('device_bans') && Schema::hasColumn('device_bans', 'source')) {
            Schema::table('device_bans', function (Blueprint $table) {
                $table->dropIndex('idx_ban_fp_source_active');
                $table->dropIndex('idx_ban_source');
                $table->dropColumn('source');
            });
        }

        if (Schema::hasTable('login_attempts') && Schema::hasColumn('login_attempts', 'source')) {
            Schema::table('login_attempts', function (Blueprint $table) {
                $table->dropIndex('idx_login_fp_source_created');
                $table->dropIndex('idx_login_source');
                $table->dropColumn('source');
            });
        }
    }
};
