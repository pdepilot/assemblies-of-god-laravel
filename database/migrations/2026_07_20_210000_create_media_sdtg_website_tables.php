<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sermon_categories')) {
            Schema::create('sermon_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 140)->unique('uk_sermon_cat_slug');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active', 'idx_sermon_cat_active');
            });
        }

        if (! Schema::hasTable('sermon_series')) {
            Schema::create('sermon_series', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255);
                $table->string('slug', 255)->unique('uk_sermon_series_slug');
                $table->text('description')->nullable();
                $table->string('cover_image', 500)->nullable();
                $table->string('minister_name', 255)->default('');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('is_active', 'idx_sermon_series_active');
            });
        }

        if (! Schema::hasTable('live_streams')) {
            Schema::create('live_streams', function (Blueprint $table) {
                $table->increments('id');
                $table->string('stream_code', 24)->unique('uk_live_stream_code');
                $table->string('title', 255);
                $table->string('slug', 255)->unique('uk_live_stream_slug');
                $table->text('description')->nullable();
                $table->string('minister_name', 255)->default('');
                $table->string('minister_photo', 500)->nullable();
                $table->string('thumbnail', 500)->nullable();
                $table->string('banner_image', 500)->nullable();
                $table->string('broadcast_type', 20)->default('video');
                $table->date('stream_date');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->string('platform', 40)->default('youtube');
                $table->string('embed_url', 1000)->default('');
                $table->string('stream_url', 1000)->default('');
                $table->string('audio_stream_url', 1000)->default('');
                $table->string('video_stream_url', 1000)->default('');
                $table->mediumText('custom_embed_code')->nullable();
                $table->string('status', 20)->default('scheduled');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('viewer_count')->default(0);
                $table->unsignedInteger('listener_count')->default(0);
                $table->unsignedInteger('peak_listeners')->default(0);
                $table->unsignedInteger('total_listeners')->default(0);
                $table->unsignedInteger('peak_viewers')->default(0);
                $table->unsignedInteger('total_viewers')->default(0);
                $table->unsignedInteger('converted_sermon_id')->nullable();
                $table->boolean('reminder_sent')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->timestamps();
                $table->index('status', 'idx_live_stream_status');
                $table->index('stream_date', 'idx_live_stream_date');
            });
        }

        if (! Schema::hasTable('sermons')) {
            Schema::create('sermons', function (Blueprint $table) {
                $table->increments('id');
                $table->string('sermon_code', 24)->unique('uk_sermon_code');
                $table->string('title', 255);
                $table->string('slug', 255)->unique('uk_sermon_slug');
                $table->text('description')->nullable();
                $table->mediumText('content_html')->nullable();
                $table->string('scripture_refs', 500)->default('');
                $table->date('sermon_date');
                $table->string('minister_name', 255)->default('');
                $table->string('minister_position', 255)->default('');
                $table->string('minister_photo', 500)->nullable();
                $table->unsignedInteger('category_id')->nullable();
                $table->unsignedInteger('series_id')->nullable();
                $table->json('tags')->nullable();
                $table->string('sermon_type', 20)->default('audio');
                $table->string('status', 20)->default('draft');
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_trending')->default(false);
                $table->dateTime('scheduled_at')->nullable();
                $table->string('featured_image', 500)->nullable();
                $table->string('banner_image', 500)->nullable();
                $table->string('seo_title', 255)->default('');
                $table->string('seo_description', 500)->default('');
                $table->string('seo_keywords', 500)->default('');
                $table->string('youtube_url', 1000)->default('');
                $table->string('facebook_video_url', 1000)->default('');
                $table->string('vimeo_url', 1000)->default('');
                $table->string('video_file_path', 500)->nullable();
                $table->mediumText('video_embed_code')->nullable();
                $table->string('audio_file_path', 500)->nullable();
                $table->string('audio_stream_url', 1000)->default('');
                $table->string('podcast_url', 1000)->default('');
                $table->string('pdf_file_path', 500)->nullable();
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->unsignedInteger('view_count')->default(0);
                $table->unsignedInteger('download_count')->default(0);
                $table->unsignedInteger('play_count')->default(0);
                $table->unsignedInteger('share_count')->default(0);
                $table->unsignedInteger('live_stream_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->dateTime('archived_at')->nullable();
                $table->boolean('show_on_homepage')->default(false);
                $table->unsignedTinyInteger('homepage_order')->default(0);
                $table->timestamps();
                $table->index('status', 'idx_sermon_status');
                $table->index('sermon_type', 'idx_sermon_type');
                $table->index('sermon_date', 'idx_sermon_date');
                $table->index('is_featured', 'idx_sermon_featured');
                $table->index('category_id', 'idx_sermon_category');
                $table->index('series_id', 'idx_sermon_series');
            });
        }

        if (! Schema::hasTable('sermon_media_library')) {
            Schema::create('sermon_media_library', function (Blueprint $table) {
                $table->increments('id');
                $table->string('file_name', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->default('');
                $table->string('file_path', 500);
                $table->unsignedInteger('file_size')->default(0);
                $table->string('media_type', 20)->default('document');
                $table->string('alt_text', 255)->default('');
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('media_type', 'idx_sermon_media_type');
            });
        }

        if (! Schema::hasTable('broadcast_platforms')) {
            Schema::create('broadcast_platforms', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('broadcast_id');
                $table->string('platform', 40);
                $table->string('embed_url', 1000)->default('');
                $table->string('stream_url', 1000)->default('');
                $table->string('audio_stream_url', 1000)->default('');
                $table->string('video_stream_url', 1000)->default('');
                $table->mediumText('custom_embed_code')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['broadcast_id', 'platform'], 'uk_broadcast_platform');
                $table->index('broadcast_id', 'idx_broadcast_platforms_broadcast');
            });
        }

        if (! Schema::hasTable('ag_site_content')) {
            Schema::create('ag_site_content', function (Blueprint $table) {
                $table->increments('id');
                $table->string('section_key', 80)->unique('uk_ag_content_section');
                $table->longText('content_json')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ag_blog_posts')) {
            Schema::create('ag_blog_posts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255);
                $table->string('slug', 255)->unique('uk_ag_blog_slug');
                $table->string('excerpt', 500)->default('');
                $table->mediumText('body_html');
                $table->string('author', 120)->default('AGC Ikenegbu');
                $table->string('category', 50)->default('church-news');
                $table->string('featured_image', 500)->default('');
                $table->string('meta_description', 255)->default('');
                $table->boolean('is_published')->default(false);
                $table->dateTime('published_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['is_published', 'published_at'], 'idx_ag_blog_published');
                $table->index('category', 'idx_ag_blog_category');
            });
        }

        if (! Schema::hasTable('site_team_members')) {
            Schema::create('site_team_members', function (Blueprint $table) {
                $table->increments('id');
                $table->string('site', 10)->default('ag');
                $table->string('member_type', 20)->default('member');
                $table->string('full_name', 150);
                $table->string('role_title', 120)->default('');
                $table->text('bio')->nullable();
                $table->string('photo_path', 500)->nullable();
                $table->string('social_facebook', 500)->nullable();
                $table->string('social_twitter', 500)->nullable();
                $table->string('social_instagram', 500)->nullable();
                $table->string('social_linkedin', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['site', 'is_active', 'sort_order'], 'idx_team_site_active');
                $table->index(['site', 'member_type'], 'idx_team_site_type');
            });
        }

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $categories = [
            ['name' => 'Sermons', 'slug' => 'sermons', 'description' => 'Sunday messages and Bible teaching', 'sort_order' => 1],
            ['name' => 'Bible Study', 'slug' => 'bible_study', 'description' => 'Midweek Bible study teachings', 'sort_order' => 2],
            ['name' => 'Special Programs', 'slug' => 'special_programs', 'description' => 'Special church programs and events', 'sort_order' => 3],
        ];
        foreach ($categories as $category) {
            DB::table('sermon_categories')->insertOrIgnore($category + [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_team_members');
        Schema::dropIfExists('ag_blog_posts');
        Schema::dropIfExists('ag_site_content');
        Schema::dropIfExists('broadcast_platforms');
        Schema::dropIfExists('sermon_media_library');
        Schema::dropIfExists('sermons');
        Schema::dropIfExists('live_streams');
        Schema::dropIfExists('sermon_series');
        Schema::dropIfExists('sermon_categories');
    }
};
