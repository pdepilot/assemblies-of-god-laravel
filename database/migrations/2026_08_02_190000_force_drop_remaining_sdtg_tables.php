<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Force-remove any leftover SDTG tables from assemblies_of_god.
 * SDTG lives in its own application and must not share this database.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = $this->sdtgTables();

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->purgeSdtgRows();
    }

    public function down(): void
    {
        // Intentionally empty — SDTG schema belongs in the separate SDTG app.
    }

    /** @return list<string> */
    private function sdtgTables(): array
    {
        $known = [
            'sdtg_livestream_settings',
            'sdtg_livestream_stats',
            'sdtg_memory_submissions',
            'sdtg_prayer_requests',
            'sdtg_testimonials',
            'sdtg_media_assets',
            'sdtg_media_folders',
            'sdtg_gallery_items',
            'sdtg_gallery_albums',
            'sdtg_announcements',
            'sdtg_sponsors',
            'sdtg_site_content',
            'sdtg_crusade_editions',
            'sdtg_speakers',
            'sdtg_registrations',
            'sdtg_admins',
            'sdtg_users',
            'sdtg_settings',
        ];

        $found = [];
        try {
            $rows = DB::select('SHOW TABLES');
            if ($rows !== []) {
                $key = array_key_first((array) $rows[0]);
                foreach ($rows as $row) {
                    $name = (string) $row->$key;
                    if (str_starts_with(strtolower($name), 'sdtg_') || str_starts_with(strtolower($name), 'sdgt_')) {
                        $found[] = $name;
                    }
                }
            }
        } catch (\Throwable) {
            // Fall back to the known list below.
        }

        return array_values(array_unique(array_merge($known, $found)));
    }

    private function purgeSdtgRows(): void
    {
        if (Schema::hasTable('donation_funds')) {
            if (Schema::hasColumn('donation_funds', 'code')) {
                DB::table('donation_funds')->where('code', 'sdtg')->delete();
            }
            if (Schema::hasColumn('donation_funds', 'slug')) {
                DB::table('donation_funds')->where('slug', 'sdtg')->delete();
            }
        }

        if (Schema::hasTable('donation_categories')) {
            DB::table('donation_categories')->where(function ($query): void {
                if (Schema::hasColumn('donation_categories', 'slug')) {
                    $query->orWhere('slug', 'sdtg');
                }
                if (Schema::hasColumn('donation_categories', 'code')) {
                    $query->orWhere('code', 'sdtg');
                }
            })->delete();
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'platform')) {
            DB::table('roles')->where('platform', 'sdtg')->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('module', 'sdtg')->delete();
            if (Schema::hasColumn('permissions', 'permission_key')) {
                DB::table('permissions')->where('permission_key', 'like', 'sdtg.%')->delete();
            }
        }

        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'platform_access')) {
            DB::table('admins')->where('platform_access', 'sdtg')->delete();
            DB::table('admins')->where('platform_access', 'both')->update(['platform_access' => 'ag']);
        }

        if (Schema::hasTable('church_events') && Schema::hasColumn('church_events', 'category')) {
            DB::table('church_events')->where('category', 'sdtg')->update(['category' => 'other']);
        }

        if (Schema::hasTable('donations') && Schema::hasColumn('donations', 'fund_scope')) {
            DB::table('donations')->where('fund_scope', 'sdtg')->update(['fund_scope' => 'church']);
        }
    }
};
