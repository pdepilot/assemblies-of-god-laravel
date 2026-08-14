<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove SDTG tables from the AG church database after SDTG was extracted
 * to its own application.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
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
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            foreach (self::TABLES as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Remove SDTG-only donation catalog seed if present.
        if (Schema::hasTable('donation_funds')) {
            if (Schema::hasColumn('donation_funds', 'code')) {
                DB::table('donation_funds')->where('code', 'sdtg')->delete();
            }
            if (Schema::hasColumn('donation_funds', 'slug')) {
                DB::table('donation_funds')->where('slug', 'sdtg')->delete();
            }
        }
        if (Schema::hasTable('donation_categories')) {
            $query = DB::table('donation_categories');
            $query->where(function ($inner): void {
                if (Schema::hasColumn('donation_categories', 'slug')) {
                    $inner->orWhere('slug', 'sdtg');
                }
                if (Schema::hasColumn('donation_categories', 'code')) {
                    $inner->orWhere('code', 'sdtg');
                }
            })->delete();
        }

        // Roles / admins that were SDTG-only no longer belong in this app.
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'platform')) {
            DB::table('roles')->where('platform', 'sdtg')->delete();
        }
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('module', 'sdtg')->delete();
            DB::table('permissions')->where('permission_key', 'like', 'sdtg.%')->delete();
        }
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'platform_access')) {
            DB::table('admins')->where('platform_access', 'sdtg')->delete();
            DB::table('admins')->where('platform_access', 'both')->update(['platform_access' => 'ag']);
        }

        // Reclassify leftover SDTG event / donation rows as church-scoped.
        if (Schema::hasTable('church_events')) {
            DB::table('church_events')->where('category', 'sdtg')->update(['category' => 'other']);
        }
        if (Schema::hasTable('donations') && Schema::hasColumn('donations', 'fund_scope')) {
            DB::table('donations')->where('fund_scope', 'sdtg')->update(['fund_scope' => 'church']);
        }
    }

    public function down(): void
    {
        // Intentionally empty — SDTG schema lives in the separate SDTG app.
    }
};
