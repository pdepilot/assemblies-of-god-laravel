<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures platform_access exists on admins (needed on SDTG DB after identity copy
 * if the column was missing from the cloned schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        if (! Schema::hasColumn('admins', 'platform_access')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('platform_access', 16)
                    ->default('both')
                    ->after('account_status');
            });
        }

        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'platform')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('platform', 16)
                    ->default('both')
                    ->after('slug');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive companion migration; leave columns in place on rollback.
    }
};
