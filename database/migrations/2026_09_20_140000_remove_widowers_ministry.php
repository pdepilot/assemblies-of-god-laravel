<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ministry_settings')) {
            DB::table('ministry_settings')->where('ministry_key', 'widowers')->delete();
        }

        if (Schema::hasTable('members') && Schema::hasColumn('members', 'department')) {
            DB::table('members')
                ->where('department', 'Widowers Ministry')
                ->update(['department' => "Men's Ministry"]);
        }

        if (Schema::hasTable('ministry_members')) {
            $rows = DB::table('ministry_members')->where('ministry_key', 'widowers')->get();
            foreach ($rows as $row) {
                $alreadyInMen = DB::table('ministry_members')
                    ->where('member_id', $row->member_id)
                    ->where('ministry_key', 'men')
                    ->exists();
                if ($alreadyInMen) {
                    DB::table('ministry_members')->where('id', $row->id)->delete();
                } else {
                    DB::table('ministry_members')->where('id', $row->id)->update(['ministry_key' => 'men']);
                }
            }
        }

        if (Schema::hasTable('ministry_roster_people')) {
            DB::table('ministry_roster_people')
                ->where('ministry_key', 'widowers')
                ->update(['ministry_key' => 'men']);
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'dashboard_type')) {
            DB::table('roles')
                ->where('dashboard_type', 'widowers')
                ->update(['dashboard_type' => 'men']);
        }

        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'module')) {
            DB::table('permissions')->where('module', 'widowers')->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ministry_settings')) {
            return;
        }

        if (DB::table('ministry_settings')->where('ministry_key', 'widowers')->exists()) {
            return;
        }

        $now = now();
        DB::table('ministry_settings')->insert([
            'ministry_key' => 'widowers',
            'name' => 'Widowers Ministry',
            'min_age' => 20,
            'max_age' => 120,
            'gender_filter' => 'male',
            'assignment_mode' => 'auto',
            'is_enabled' => true,
            'sort_order' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
