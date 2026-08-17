<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_newsletter_subscribers')) {
            return;
        }

        Schema::table('site_newsletter_subscribers', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_newsletter_subscribers', 'country')) {
                $table->string('country', 80)->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'region')) {
                $table->string('region', 80)->nullable()->after('country');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'city')) {
                $table->string('city', 80)->nullable()->after('region');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('city');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'location_source')) {
                $table->string('location_source', 20)->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'location_accuracy')) {
                $table->string('location_accuracy', 20)->nullable()->after('location_source');
            }
            if (! Schema::hasColumn('site_newsletter_subscribers', 'location_updated_at')) {
                $table->timestamp('location_updated_at')->nullable()->after('location_accuracy');
            }
        });

        if (Schema::hasColumn('site_newsletter_subscribers', 'location_source')) {
            DB::table('site_newsletter_subscribers')
                ->whereNotNull('ip_address')
                ->where('ip_address', '!=', '')
                ->whereNull('location_source')
                ->update([
                    'location_source' => 'ip',
                    'location_accuracy' => 'estimated',
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_newsletter_subscribers')) {
            return;
        }

        Schema::table('site_newsletter_subscribers', function (Blueprint $table): void {
            foreach (['location_updated_at', 'location_accuracy', 'location_source', 'longitude', 'latitude', 'city', 'region', 'country'] as $column) {
                if (Schema::hasColumn('site_newsletter_subscribers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
