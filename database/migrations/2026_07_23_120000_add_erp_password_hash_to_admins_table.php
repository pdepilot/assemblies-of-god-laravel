<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        if (! Schema::hasColumn('admins', 'erp_password_hash')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('erp_password_hash')->nullable()->after('password_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'erp_password_hash')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('erp_password_hash');
            });
        }
    }
};
