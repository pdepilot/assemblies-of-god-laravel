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

        if (! Schema::hasTable('sdtg_registrations')) {
            Schema::create('sdtg_registrations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('registration_code', 20)->unique('uk_sdtg_reg_code');
                $table->string('event_id', 50)->default('sdtg-2026');
                $table->string('full_name', 255);
                $table->string('email', 255)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('country', 100)->default('Nigeria');
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('church_name', 255)->nullable();
                $table->string('ticket_type', 20)->default('General');
                $table->string('attendance_mode', 20)->nullable();
                $table->boolean('is_volunteer')->default(false);
                $table->string('volunteer_status', 20)->default('none');
                $table->string('volunteer_role', 100)->nullable();
                $table->string('volunteer_shift', 120)->nullable();
                $table->string('volunteer_days', 255)->nullable();
                $table->date('registration_date');
                $table->string('status', 20)->default('confirmed');
                $table->string('source', 20)->default('website');
                $table->text('notes')->nullable();
                $table->json('form_data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('registration_date', 'idx_sdtg_reg_date');
                $table->index('country', 'idx_sdtg_reg_country');
                $table->index(['event_id', 'email'], 'idx_sdtg_reg_event_email');
                $table->index(['is_volunteer', 'volunteer_status'], 'idx_sdtg_volunteer_status');
            });
        }

        if (! Schema::hasTable('sdtg_speakers')) {
            Schema::create('sdtg_speakers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('slug', 120)->default('')->unique('uk_speaker_slug');
                $table->string('full_name', 255);
                $table->string('ministry', 255)->default('');
                $table->string('country', 100)->default('Nigeria');
                $table->unsignedSmallInteger('crusade_year');
                $table->string('speaker_type', 20)->default('upcoming');
                $table->string('status', 20)->default('pending');
                $table->text('bio')->nullable();
                $table->string('topic', 255)->nullable();
                $table->text('session_info')->nullable();
                $table->string('host_role', 100)->nullable();
                $table->text('appearances')->nullable();
                $table->string('past_years', 120)->nullable();
                $table->string('photo_path', 255)->nullable();
                $table->json('gallery_json')->nullable();
                $table->json('social_json')->nullable();
                $table->string('video_type', 20)->nullable();
                $table->string('video_src', 500)->nullable();
                $table->string('video_thumb', 500)->nullable();
                $table->string('video_label', 255)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index('crusade_year', 'idx_speaker_year');
                $table->index(['speaker_type', 'is_published'], 'idx_speaker_type');
            });
        }

        if (! Schema::hasTable('sdtg_crusade_editions')) {
            Schema::create('sdtg_crusade_editions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedSmallInteger('crusade_year')->unique('uk_sdtg_edition_year');
                $table->string('theme', 255);
                $table->text('speakers_summary');
                $table->text('highlights');
                $table->dateTime('event_start_at')->nullable();
                $table->dateTime('event_end_at')->nullable();
                $table->string('venue', 255)->default('Owerri, Nigeria');
                $table->boolean('is_next_crusade')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
                $table->index(['is_published', 'sort_order'], 'idx_edition_published');
            });
        }

        if (! Schema::hasTable('sdtg_site_content')) {
            Schema::create('sdtg_site_content', function (Blueprint $table) {
                $table->increments('id');
                $table->string('section_key', 80)->unique('uk_sdtg_content_section');
                $table->longText('content_json')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sdtg_sponsors')) {
            Schema::create('sdtg_sponsors', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 200);
                $table->string('tier', 60)->default('partner');
                $table->string('logo_path', 500)->nullable();
                $table->string('website_url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
                $table->index(['is_published', 'sort_order'], 'idx_sdtg_sponsor_pub');
            });
        }

        if (! Schema::hasTable('sdtg_announcements')) {
            Schema::create('sdtg_announcements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255);
                $table->string('slug', 120)->default('');
                $table->text('excerpt')->nullable();
                $table->text('body')->nullable();
                $table->string('category', 80)->default('Announcement');
                $table->string('image_path', 500)->nullable();
                $table->string('link_url', 500)->nullable();
                $table->dateTime('published_at')->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['is_published', 'published_at', 'sort_order'], 'idx_sdtg_ann_pub');
            });
        }

        if (! Schema::hasTable('sdtg_gallery_albums')) {
            Schema::create('sdtg_gallery_albums', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 255);
                $table->string('slug', 120)->default('')->unique('uk_gallery_album_slug');
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('crusade_year');
                $table->string('cover_path', 500)->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['crusade_year', 'is_published'], 'idx_gallery_album_year');
            });
        }

        if (! Schema::hasTable('sdtg_gallery_items')) {
            Schema::create('sdtg_gallery_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('album_id')->nullable();
                $table->unsignedInteger('source_memory_id')->nullable();
                $table->string('media_type', 20)->default('photo');
                $table->string('title', 255);
                $table->text('caption')->nullable();
                $table->string('category', 20)->default('highlights');
                $table->string('layout_size', 10)->default('md');
                $table->string('file_path', 500)->nullable();
                $table->string('external_url', 500)->nullable();
                $table->string('video_type', 20)->nullable();
                $table->string('video_src', 500)->nullable();
                $table->string('thumbnail_path', 500)->nullable();
                $table->unsignedSmallInteger('crusade_year');
                $table->string('tags', 255)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_speakers_highlight')->default(false);
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('file_size')->nullable();
                $table->timestamps();
                $table->index('album_id', 'idx_gallery_item_album');
                $table->index(['is_published', 'media_type', 'category'], 'idx_gallery_item_pub');
            });
        }

        if (! Schema::hasTable('sdtg_media_folders')) {
            Schema::create('sdtg_media_folders', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 120)->unique('uk_sdtg_media_folder_slug');
                $table->string('description', 255)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sdtg_media_assets')) {
            Schema::create('sdtg_media_assets', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('folder_id')->nullable();
                $table->string('media_type', 20)->default('image');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('tags', 255)->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('external_url', 500)->nullable();
                $table->string('thumbnail_path', 500)->nullable();
                $table->string('video_type', 20)->nullable();
                $table->string('video_src', 500)->nullable();
                $table->string('duration_label', 20)->nullable();
                $table->unsignedSmallInteger('crusade_year');
                $table->string('status', 20)->default('draft');
                $table->unsignedInteger('file_size')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index('folder_id', 'idx_sdtg_media_folder');
                $table->index(['media_type', 'status', 'crusade_year'], 'idx_sdtg_media_type');
            });
        }

        if (! Schema::hasTable('sdtg_testimonials')) {
            Schema::create('sdtg_testimonials', function (Blueprint $table) {
                $table->increments('id');
                $table->string('full_name', 150);
                $table->string('email', 255);
                $table->string('location', 150)->nullable();
                $table->string('crusade_year', 20)->nullable();
                $table->text('testimony_text');
                $table->unsignedTinyInteger('rating')->default(5);
                $table->string('photo_path', 500)->nullable();
                $table->string('source', 20)->default('home');
                $table->string('status', 20)->default('pending');
                $table->boolean('is_featured')->default(false);
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->dateTime('reviewed_at')->nullable();
                $table->index(['status', 'created_at'], 'idx_sdtg_testimony_status');
            });
        }

        if (! Schema::hasTable('sdtg_prayer_requests')) {
            Schema::create('sdtg_prayer_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('full_name', 150);
                $table->string('email', 255);
                $table->text('request_text');
                $table->string('status', 20)->default('new');
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['status', 'created_at'], 'idx_sdtg_prayer_status');
            });
        }

        if (! Schema::hasTable('sdtg_memory_submissions')) {
            Schema::create('sdtg_memory_submissions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('full_name', 150);
                $table->string('email', 255);
                $table->string('edition', 50)->nullable();
                $table->text('testimony_text')->nullable();
                $table->json('photo_paths')->nullable();
                $table->json('video_paths')->nullable();
                $table->string('status', 20)->default('pending');
                $table->dateTime('published_to_gallery_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['status', 'created_at'], 'idx_sdtg_memory_status');
            });
        }

        if (! Schema::hasTable('sdtg_livestream_stats')) {
            Schema::create('sdtg_livestream_stats', function (Blueprint $table) {
                $table->increments('id');
                $table->string('session_name', 255)->default('SDTG Livestream');
                $table->unsignedInteger('peak_viewers')->default(0);
                $table->date('stat_date');
                $table->timestamp('created_at')->useCurrent();
                $table->index('stat_date', 'idx_livestream_date');
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
                $table->string('author', 120)->default('AG Ikenebgu');
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

        DB::table('sdtg_crusade_editions')->insertOrIgnore([
            'crusade_year' => 2026,
            'theme' => 'Sound Doctrine Crusade 2026',
            'speakers_summary' => 'Anointed ministers from across Nigeria and beyond.',
            'highlights' => 'Worship, teaching, healing, and community outreach.',
            'event_start_at' => '2026-08-01 09:00:00',
            'event_end_at' => '2026-08-03 21:00:00',
            'venue' => 'Owerri, Nigeria',
            'is_next_crusade' => true,
            'sort_order' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_team_members');
        Schema::dropIfExists('ag_blog_posts');
        Schema::dropIfExists('ag_site_content');
        Schema::dropIfExists('sdtg_livestream_stats');
        Schema::dropIfExists('sdtg_memory_submissions');
        Schema::dropIfExists('sdtg_prayer_requests');
        Schema::dropIfExists('sdtg_testimonials');
        Schema::dropIfExists('sdtg_media_assets');
        Schema::dropIfExists('sdtg_media_folders');
        Schema::dropIfExists('sdtg_gallery_items');
        Schema::dropIfExists('sdtg_gallery_albums');
        Schema::dropIfExists('sdtg_announcements');
        Schema::dropIfExists('sdtg_sponsors');
        Schema::dropIfExists('sdtg_site_content');
        Schema::dropIfExists('sdtg_crusade_editions');
        Schema::dropIfExists('sdtg_speakers');
        Schema::dropIfExists('sdtg_registrations');
        Schema::dropIfExists('broadcast_platforms');
        Schema::dropIfExists('sermon_media_library');
        Schema::dropIfExists('sermons');
        Schema::dropIfExists('live_streams');
        Schema::dropIfExists('sermon_series');
        Schema::dropIfExists('sermon_categories');
    }
};
