<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ag_blog_posts')) {
            Schema::table('ag_blog_posts', function (Blueprint $table): void {
                if (! Schema::hasColumn('ag_blog_posts', 'tags')) {
                    $table->json('tags')->nullable()->after('category');
                }
                if (! Schema::hasColumn('ag_blog_posts', 'reading_time_minutes')) {
                    $table->unsignedSmallInteger('reading_time_minutes')->default(0)->after('meta_description');
                }
                if (! Schema::hasColumn('ag_blog_posts', 'view_count')) {
                    $table->unsignedInteger('view_count')->default(0)->after('reading_time_minutes');
                }
                if (! Schema::hasColumn('ag_blog_posts', 'featured_image_alt')) {
                    $table->string('featured_image_alt', 255)->default('')->after('featured_image');
                }
                if (! Schema::hasColumn('ag_blog_posts', 'seo_title')) {
                    $table->string('seo_title', 255)->default('')->after('meta_description');
                }
            });
        }

        if (Schema::hasTable('site_newsletter_subscribers')) {
            Schema::table('site_newsletter_subscribers', function (Blueprint $table): void {
                if (! Schema::hasColumn('site_newsletter_subscribers', 'confirm_token')) {
                    $table->string('confirm_token', 64)->nullable()->after('status');
                }
                if (! Schema::hasColumn('site_newsletter_subscribers', 'confirmed_at')) {
                    $table->dateTime('confirmed_at')->nullable()->after('confirm_token');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ag_blog_posts')) {
            Schema::table('ag_blog_posts', function (Blueprint $table): void {
                foreach (['tags', 'reading_time_minutes', 'view_count', 'featured_image_alt', 'seo_title'] as $col) {
                    if (Schema::hasColumn('ag_blog_posts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('site_newsletter_subscribers')) {
            Schema::table('site_newsletter_subscribers', function (Blueprint $table): void {
                foreach (['confirm_token', 'confirmed_at'] as $col) {
                    if (Schema::hasColumn('site_newsletter_subscribers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
