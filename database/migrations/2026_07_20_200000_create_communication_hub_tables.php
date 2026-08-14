<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_channels')) {
            Schema::create('communication_channels', function (Blueprint $table) {
                $table->tinyIncrements('id');
                $table->string('channel_key', 40)->unique('uk_comm_channel_key');
                $table->string('label', 80);
                $table->boolean('is_enabled')->default(false);
                $table->boolean('is_available')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->json('meta_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('communication_settings')) {
            Schema::create('communication_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('default_channel', 40)->default('email');
                $table->boolean('sms_enabled')->default(false);
                $table->string('sms_provider', 40)->default('termii');
                $table->string('sms_sender_id', 32)->default('AGIKENEGBU');
                $table->string('sms_api_key', 255)->nullable();
                $table->string('sms_api_secret', 255)->nullable();
                $table->string('sms_base_url', 255)->nullable();
                $table->decimal('sms_balance_cache', 12, 2)->nullable();
                $table->boolean('whatsapp_enabled')->default(false);
                $table->boolean('push_enabled')->default(false);
                $table->boolean('birthday_auto_email_enabled')->default(true);
                $table->unsignedInteger('rate_limit_per_minute')->default(120);
                $table->unsignedTinyInteger('retry_max_attempts')->default(3);
                $table->string('brand_motto', 255)->nullable();
                $table->string('brand_theme_primary', 20)->nullable()->default('#1A2B5C');
                $table->string('brand_theme_accent', 20)->nullable()->default('#D4AF37');
                $table->boolean('include_pastor_signature')->default(true);
                $table->boolean('include_qr_code')->default(false);
                $table->string('qr_code_url', 500)->nullable();
                $table->mediumText('pastor_signature_html')->nullable();
                $table->mediumText('newsletter_default_footer')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('updated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('communication_template_categories')) {
            Schema::create('communication_template_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique('uk_comm_tpl_cat_slug');
                $table->string('name', 120);
                $table->string('description', 255)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('communication_templates')) {
            Schema::create('communication_templates', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 120);
                $table->string('name', 255);
                $table->string('category_slug', 80)->default('general');
                $table->string('channel', 32)->default('email');
                $table->string('subject', 500)->nullable();
                $table->mediumText('body_html')->nullable();
                $table->mediumText('body_text')->nullable();
                $table->json('merge_fields_json')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->boolean('is_system')->default(false);
                $table->string('status', 32)->default('published');
                $table->unsignedBigInteger('email_template_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['slug', 'channel'], 'uk_comm_tpl_slug_channel');
                $table->index('status', 'idx_comm_tpl_status');
                $table->index('category_slug', 'idx_comm_tpl_category');
            });
        }

        if (! Schema::hasTable('communication_logs')) {
            Schema::create('communication_logs', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 40);
                $table->string('direction', 16)->default('outbound');
                $table->string('subject', 500)->nullable();
                $table->string('sender', 255)->nullable();
                $table->string('recipient', 255);
                $table->string('recipient_name', 255)->nullable();
                $table->string('template_slug', 120)->nullable();
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->unsignedBigInteger('automation_rule_id')->nullable();
                $table->string('status', 40)->default('queued');
                $table->string('priority', 16)->default('normal');
                $table->boolean('opened')->default(false);
                $table->boolean('clicked')->default(false);
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->text('gateway_response')->nullable();
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->text('error_message')->nullable();
                $table->string('ref_table', 80)->nullable();
                $table->unsignedBigInteger('ref_id')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['channel', 'created_at'], 'idx_comm_logs_channel');
                $table->index('status', 'idx_comm_logs_status');
                $table->index('recipient', 'idx_comm_logs_recipient');
            });
        }

        if (! Schema::hasTable('newsletter_drafts')) {
            Schema::create('newsletter_drafts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('status', 32)->default('draft');
                $table->json('sections_json');
                $table->mediumText('html_preview')->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_center')) {
            Schema::create('notification_center', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->unsignedBigInteger('member_id')->nullable();
                $table->string('category', 80)->default('general');
                $table->string('title', 255);
                $table->text('body')->nullable();
                $table->string('priority', 16)->default('normal');
                $table->boolean('is_read')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->string('link_url', 500)->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->dateTime('read_at')->nullable();
                $table->index(['admin_id', 'is_read'], 'idx_notif_admin_read');
            });
        }

        if (! Schema::hasTable('email_settings')) {
            Schema::create('email_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('provider', 32)->default('smtp');
                $table->string('from_email', 255)->default('noreply@agikenebgu.com');
                $table->string('from_name', 255)->default('Assemblies of God Ikenegbu');
                $table->string('reply_to', 255)->nullable();
                $table->string('smtp_host', 255)->nullable();
                $table->unsignedInteger('smtp_port')->default(587);
                $table->string('smtp_user', 255)->nullable();
                $table->string('smtp_pass', 255)->nullable();
                $table->string('church_address', 500)->nullable();
                $table->string('church_phone', 50)->nullable();
                $table->string('church_email', 255)->nullable();
                $table->string('church_website', 255)->nullable();
                $table->string('pastor_name', 255)->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 100)->unique('uk_email_templates_slug');
                $table->string('name', 255);
                $table->string('category', 80)->default('general');
                $table->string('subject', 500);
                $table->mediumText('body_html');
                $table->boolean('is_system')->default(false);
                $table->string('status', 32)->default('published');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_history')) {
            Schema::create('email_history', function (Blueprint $table) {
                $table->id();
                $table->string('tracking_token', 64)->unique('uk_email_history_token');
                $table->string('subject', 500);
                $table->string('recipient', 255);
                $table->string('recipient_name', 255)->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('template_slug', 100)->nullable();
                $table->string('recipient_group', 80)->nullable();
                $table->string('batch_id', 64)->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->string('provider', 30)->nullable();
                $table->string('status', 32)->default('queued');
                $table->boolean('opened')->default(false);
                $table->boolean('clicked')->default(false);
                $table->unsignedInteger('open_count')->default(0);
                $table->unsignedInteger('click_count')->default(0);
                $table->text('error_message')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('recipient', 'idx_email_history_recipient');
                $table->index('status', 'idx_email_history_status');
                $table->index('created_at', 'idx_email_history_created');
            });
        }

        if (! Schema::hasTable('contact_submissions')) {
            Schema::create('contact_submissions', function (Blueprint $table) {
                $table->id();
                $table->string('submission_code', 20)->unique('uk_contact_submission_code');
                $table->string('inquiry_type', 32)->default('general');
                $table->string('full_name', 255);
                $table->string('email', 255);
                $table->string('phone', 30)->nullable();
                $table->string('subject', 255);
                $table->text('message');
                $table->string('status', 32)->default('new');
                $table->text('admin_notes')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->dateTime('read_at')->nullable();
                $table->dateTime('replied_at')->nullable();
                $table->dateTime('ack_sent_at')->nullable();
                $table->string('reply_subject', 255)->nullable();
                $table->text('reply_body')->nullable();
                $table->unsignedBigInteger('handled_by')->nullable();
                $table->timestamps();
                $table->index('inquiry_type', 'idx_contact_type');
                $table->index('status', 'idx_contact_status');
                $table->index('created_at', 'idx_contact_created');
            });
        }

        if (! Schema::hasTable('site_newsletter_subscribers')) {
            Schema::create('site_newsletter_subscribers', function (Blueprint $table) {
                $table->id();
                $table->string('email', 255)->unique('uk_site_newsletter_email');
                $table->string('source', 50)->default('footer');
                $table->string('status', 32)->default('active');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->dateTime('subscribed_at')->useCurrent();
                $table->dateTime('unsubscribed_at')->nullable();
                $table->dateTime('ack_sent_at')->nullable();
                $table->timestamp('updated_at')->useCurrent();
                $table->index('status', 'idx_site_newsletter_status');
                $table->index('subscribed_at', 'idx_site_newsletter_subscribed');
            });
        }

        $this->seedDefaults();
    }

    private function seedDefaults(): void
    {
        $channels = [
            ['channel_key' => 'email', 'label' => 'Email', 'is_enabled' => true, 'is_available' => true, 'sort_order' => 1],
            ['channel_key' => 'sms', 'label' => 'SMS', 'is_enabled' => false, 'is_available' => true, 'sort_order' => 2],
            ['channel_key' => 'whatsapp', 'label' => 'WhatsApp', 'is_enabled' => false, 'is_available' => false, 'sort_order' => 3],
            ['channel_key' => 'push', 'label' => 'Push Notifications', 'is_enabled' => false, 'is_available' => false, 'sort_order' => 4],
            ['channel_key' => 'in_app', 'label' => 'In-App Notifications', 'is_enabled' => true, 'is_available' => true, 'sort_order' => 5],
            ['channel_key' => 'voice', 'label' => 'Voice Calls', 'is_enabled' => false, 'is_available' => false, 'sort_order' => 6],
            ['channel_key' => 'newsletter', 'label' => 'Newsletter Campaigns', 'is_enabled' => true, 'is_available' => true, 'sort_order' => 7],
            ['channel_key' => 'ai', 'label' => 'AI Generated Communications', 'is_enabled' => false, 'is_available' => false, 'sort_order' => 8],
        ];
        foreach ($channels as $channel) {
            DB::table('communication_channels')->insertOrIgnore($channel + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('communication_settings')->insertOrIgnore(['id' => 1]);

        $categories = [
            ['slug' => 'birthday', 'name' => 'Birthday', 'sort_order' => 1],
            ['slug' => 'anniversary', 'name' => 'Anniversary', 'sort_order' => 2],
            ['slug' => 'welcome', 'name' => 'Welcome', 'sort_order' => 3],
            ['slug' => 'event', 'name' => 'Events', 'sort_order' => 4],
            ['slug' => 'donation', 'name' => 'Giving', 'sort_order' => 5],
            ['slug' => 'seasonal', 'name' => 'Seasonal', 'sort_order' => 6],
            ['slug' => 'prayer', 'name' => 'Prayer', 'sort_order' => 7],
            ['slug' => 'emergency', 'name' => 'Emergency', 'sort_order' => 8],
            ['slug' => 'newsletter', 'name' => 'Newsletter', 'sort_order' => 9],
            ['slug' => 'general', 'name' => 'General', 'sort_order' => 10],
        ];
        foreach ($categories as $category) {
            DB::table('communication_template_categories')->insertOrIgnore($category);
        }

        DB::table('email_settings')->insertOrIgnore([
            'id' => 1,
            'provider' => 'smtp',
            'from_email' => 'noreply@agikenebgu.com',
            'from_name' => 'Assemblies of God Ikenegbu',
        ]);

        $smsTemplates = [
            ['birthday_sms', 'Birthday SMS', 'general', 'Happy Birthday {{FirstName}}! Celebrating you today from AGC Ikenegbu. God bless you richly.'],
            ['anniversary_sms', 'Wedding Anniversary SMS', 'general', 'Happy Wedding Anniversary {{FirstName}}! Celebrating {{years}} year(s) of marriage today from AGC Ikenegbu. God bless your home.'],
            ['event_reminder_sms', 'Event Reminder SMS', 'event', 'Reminder: {{EventName}} is coming up. See you there! — AGC Ikenegbu'],
            ['donation_thanks_sms', 'Donation Thanks SMS', 'donation', 'Thank you {{FirstName}} for your gift of {{Amount}}. God bless you. — AGC Ikenegbu'],
        ];
        foreach ($smsTemplates as [$slug, $name, $cat, $body]) {
            DB::table('communication_templates')->insertOrIgnore([
                'slug' => $slug,
                'name' => $name,
                'category_slug' => $cat,
                'channel' => 'sms',
                'body_text' => $body,
                'status' => 'published',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_newsletter_subscribers');
        Schema::dropIfExists('contact_submissions');
        Schema::dropIfExists('email_history');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_settings');
        Schema::dropIfExists('notification_center');
        Schema::dropIfExists('newsletter_drafts');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('communication_templates');
        Schema::dropIfExists('communication_template_categories');
        Schema::dropIfExists('communication_settings');
        Schema::dropIfExists('communication_channels');
    }
};
