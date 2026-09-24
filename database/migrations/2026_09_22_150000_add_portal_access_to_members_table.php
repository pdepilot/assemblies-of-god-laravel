<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        if (! Schema::hasColumn('members', 'portal_enabled')) {
            Schema::table('members', function (Blueprint $table) {
                $table->boolean('portal_enabled')->default(false);
            });
        }

        if (! Schema::hasColumn('members', 'portal_password_hash')) {
            Schema::table('members', function (Blueprint $table) {
                $table->string('portal_password_hash', 255)->nullable();
            });
        }

        if (! Schema::hasColumn('members', 'portal_last_login_at')) {
            Schema::table('members', function (Blueprint $table) {
                $table->timestamp('portal_last_login_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        $drop = [];
        foreach (['portal_last_login_at', 'portal_password_hash', 'portal_enabled'] as $column) {
            if (Schema::hasColumn('members', $column)) {
                $drop[] = $column;
            }
        }

        if ($drop === []) {
            return;
        }

        Schema::table('members', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }
};
