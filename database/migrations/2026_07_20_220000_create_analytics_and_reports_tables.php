<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_sessions')) {
            Schema::create('site_sessions', function (Blueprint $table) {
                $table->id();
                $table->char('session_key', 36)->unique('uk_site_sessions_key');
                $table->char('visitor_key', 36);
                $table->dateTime('started_at');
                $table->dateTime('last_seen_at');
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->string('device_type', 20)->default('desktop');
                $table->string('browser', 60)->default('Unknown');
                $table->string('os', 60)->default('Unknown');
                $table->string('country', 100)->default('Unknown');
                $table->string('city', 120)->default('Unknown');
                $table->string('region', 120)->nullable();
                $table->char('ip_hash', 64)->default('');
                $table->string('site_area', 20)->default('ag');
                $table->string('referrer', 500)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->unsignedInteger('pageview_count')->default(0);
                $table->dateTime('created_at')->useCurrent();

                $table->index('visitor_key', 'idx_site_sessions_visitor');
                $table->index('started_at', 'idx_site_sessions_started');
                $table->index('site_area', 'idx_site_sessions_area');
                $table->index('country', 'idx_site_sessions_country');
                $table->index('device_type', 'idx_site_sessions_device');
            });
        }

        if (! Schema::hasTable('site_pageviews')) {
            Schema::create('site_pageviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id');
                $table->char('pageview_key', 36)->unique('uk_site_pageviews_key');
                $table->string('site_area', 20)->default('ag');
                $table->string('path', 500);
                $table->string('page_title', 255)->nullable();
                $table->dateTime('entered_at');
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->boolean('is_exit')->default(false);
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();

                $table->index('session_id', 'idx_site_pageviews_session');
                $table->index('entered_at', 'idx_site_pageviews_entered');
                $table->index('path', 'idx_site_pageviews_path');
                $table->index('site_area', 'idx_site_pageviews_area');
            });
        }

        if (! Schema::hasTable('site_geo_cache')) {
            Schema::create('site_geo_cache', function (Blueprint $table) {
                $table->char('ip_hash', 64)->primary();
                $table->string('country', 100)->default('Unknown');
                $table->string('city', 120)->default('Unknown');
                $table->string('region', 120)->nullable();
                $table->dateTime('fetched_at');

                $table->index('fetched_at', 'idx_site_geo_fetched');
            });
        }

        if (! Schema::hasTable('generated_reports')) {
            Schema::create('generated_reports', function (Blueprint $table) {
                $table->increments('id');
                $table->string('report_type', 50);
                $table->string('title', 255);
                $table->string('period_key', 50);
                $table->string('period_label', 120);
                $table->string('format', 20);
                $table->string('file_path', 500);
                $table->string('file_name', 255);
                $table->unsignedInteger('file_size')->default(0);
                $table->unsignedInteger('row_count')->default(0);
                $table->unsignedInteger('generated_by')->nullable();
                $table->string('generated_by_name', 255)->default('');
                $table->unsignedInteger('download_count')->default(0);
                $table->json('meta_json')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index('report_type', 'idx_report_type');
                $table->index('created_at', 'idx_created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('site_geo_cache');
        Schema::dropIfExists('site_pageviews');
        Schema::dropIfExists('site_sessions');
    }
};
