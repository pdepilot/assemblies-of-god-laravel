<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members') || Schema::hasColumn('members', 'occupation')) {
            return;
        }

        Schema::table('members', function (Blueprint $table) {
            $table->string('occupation', 120)->nullable()->after('parent_phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('members') || ! Schema::hasColumn('members', 'occupation')) {
            return;
        }

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('occupation');
        });
    }
};
