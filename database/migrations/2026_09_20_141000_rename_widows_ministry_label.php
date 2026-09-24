<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ministry_settings')) {
            DB::table('ministry_settings')
                ->where('ministry_key', 'widows')
                ->update(['name' => 'Widows']);
        }

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'department')) {
            DB::table('members')
                ->where('department', 'Widows Ministry')
                ->update(['department' => 'Widows']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ministry_settings')) {
            DB::table('ministry_settings')
                ->where('ministry_key', 'widows')
                ->update(['name' => 'Widows Ministry']);
        }

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'department')) {
            DB::table('members')
                ->where('department', 'Widows')
                ->update(['department' => 'Widows Ministry']);
        }
    }
};
