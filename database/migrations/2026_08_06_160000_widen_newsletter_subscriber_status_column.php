<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_newsletter_subscribers')) {
            return;
        }

        // Legacy MySQL used enum('active','unsubscribed'); pending broke public subscribe.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE site_newsletter_subscribers MODIFY status VARCHAR(32) NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_newsletter_subscribers')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::table('site_newsletter_subscribers')
                ->whereNotIn('status', ['active', 'unsubscribed'])
                ->update(['status' => 'active']);

            DB::statement("ALTER TABLE site_newsletter_subscribers MODIFY status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active'");
        }
    }
};
