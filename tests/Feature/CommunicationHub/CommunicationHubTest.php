<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('communications officer can view email center on laravel', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('email_history')->insert([
        'tracking_token' => Str::random(64),
        'subject' => 'Sunday Service Reminder',
        'recipient' => 'member@example.com',
        'recipient_name' => 'Test Member',
        'status' => 'sent',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.email-center.index'));

    $response->assertOk();
    $response->assertSee('Email Center');
    $response->assertSee('Sunday Service Reminder');
    $response->assertSee('Compose email');
});

test('email center embeds real template html not double escaped entities', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('email_templates')->insert([
        'slug' => 'event_invitation_test',
        'name' => 'Event Invitation Test',
        'category' => 'events',
        'subject' => 'You Are Invited: {{event_name}}',
        'body_html' => "Dear {{member_name}},\n\nJoin us for {{event_name}} on {{event_date}}.",
        'is_system' => true,
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.email-center.index'));

    $response->assertOk();
    $response->assertSee('Dear {{member_name}},', false);
    $response->assertSee('Join us for {{event_name}} on {{event_date}}.', false);
    $response->assertDontSee('<p>Dear {{member_name}}', false);
});

test('email center loads plain text volunteer template without p tags', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('email_templates')->insert([
        'slug' => 'volunteer_appreciation_test',
        'name' => 'Volunteer Appreciation Test',
        'category' => 'ministry',
        'subject' => 'Thank You For Serving',
        'body_html' => "Dear {{member_name}},\n\nThank you for your faithful service. You are a blessing to this ministry.",
        'is_system' => true,
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.email-center.index'));

    $response->assertOk();
    $response->assertSee('Thank you for your faithful service.', false);
    $response->assertDontSee('<p>Dear {{member_name}}', false);
});

test('communications officer can compose email from email center', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $this->mock(\App\Services\CommunicationHub\HubMailConfigurator::class, function ($mock) {
        $mock->shouldReceive('emailSettings')->andReturn(['from_email' => 'office@agikenebgu.com']);
        $mock->shouldReceive('sendHtml')->once();
    });

    $this->actingAs($admin, 'admin')->post(route('communication-hub.email-center.compose'), [
        'recipient_group' => 'individual',
        'individual_email' => 'guest@example.com',
        'subject' => 'Welcome to church',
        'body_html' => 'Hello and welcome',
        'priority' => 'normal',
    ])->assertRedirect(route('communication-hub.email-center.index'));

    $this->assertDatabaseHas('email_history', [
        'subject' => 'Welcome to church',
        'recipient' => 'guest@example.com',
        'status' => 'sent',
        'provider' => 'smtp',
    ]);
});

test('compose email fails clearly when smtp is not configured', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('email_settings')->insertOrIgnore([
        'id' => 1,
        'provider' => 'smtp',
        'from_email' => 'office@agikenebgu.com',
        'from_name' => 'AG Ikenebgu Office',
    ]);
    DB::table('email_settings')->where('id', 1)->update([
        'smtp_host' => null,
        'smtp_user' => null,
        'smtp_pass' => null,
        'from_email' => 'office@agikenebgu.com',
    ]);

    $this->actingAs($admin, 'admin')->post(route('communication-hub.email-center.compose'), [
        'recipient_group' => 'individual',
        'individual_email' => 'guest@example.com',
        'subject' => 'Welcome to church',
        'body_html' => 'Hello and welcome',
        'priority' => 'normal',
    ])->assertRedirect(route('communication-hub.email-center.index'));

    $this->assertDatabaseHas('email_history', [
        'recipient' => 'guest@example.com',
        'status' => 'failed',
    ]);
});

test('communications officer can create communication template', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $this->actingAs($admin, 'admin')->post(route('communication-hub.templates.store'), [
        'name' => 'Welcome Email',
        'channel' => 'email',
        'category_slug' => 'welcome',
        'subject' => 'Welcome to AG Ikenebgu',
        'body_text' => 'Hello {{FirstName}}',
    ])->assertRedirect();

    $this->assertDatabaseHas('communication_templates', [
        'name' => 'Welcome Email',
        'channel' => 'email',
        'slug' => 'welcome_email',
    ]);
});

test('communication logs fall back to email history', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    DB::table('email_history')->insert([
        'tracking_token' => Str::random(64),
        'subject' => 'Legacy email row',
        'recipient' => 'member@example.com',
        'status' => 'sent',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.logs.index'));

    $response->assertOk();
    $response->assertSee('Legacy email row');
});

test('communication logs are paginated ten per page', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $rows = [];
    for ($i = 1; $i <= 11; $i++) {
        $rows[] = [
            'channel' => 'email',
            'direction' => 'outbound',
            'subject' => "Log subject {$i}",
            'recipient' => "user{$i}@example.com",
            'status' => 'sent',
            'created_at' => now()->subMinutes($i),
        ];
    }
    DB::table('communication_logs')->insert($rows);

    // Newest id first: subjects 11–2 on page 1, subject 1 on page 2.
    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.logs.index'))
        ->assertOk()
        ->assertSee('Showing 1–10 of 11')
        ->assertSee('email — Log subject 11 —')
        ->assertDontSee('email — Log subject 1 —');

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.logs.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Showing 11–11 of 11')
        ->assertSee('email — Log subject 1 —');
});

test('communications officer can manage contact submission', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('contact_submissions')->insert([
        'submission_code' => 'CNT-TEST-001',
        'inquiry_type' => 'general',
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Need prayer',
        'message' => 'Please pray for my family.',
        'status' => 'new',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('contact_submissions')->where('submission_code', 'CNT-TEST-001')->value('id');

    $this->actingAs($admin, 'admin')->put(route('contact.submissions.update', $id), [
        'status' => 'in_progress',
        'admin_notes' => 'Assigned to prayer team',
    ])->assertRedirect();

    $this->assertDatabaseHas('contact_submissions', [
        'id' => $id,
        'status' => 'in_progress',
        'admin_notes' => 'Assigned to prayer team',
    ]);
});

test('communications officer can view and email reply to contact submission', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $id = DB::table('contact_submissions')->insertGetId([
        'submission_code' => 'CNT-TEST-REPLY',
        'inquiry_type' => 'prayer',
        'full_name' => 'Chidi Okeke',
        'email' => 'chidi@example.com',
        'subject' => 'Family prayer',
        'message' => 'Please keep my family in prayer this month.',
        'status' => 'new',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->mock(\App\Services\CommunicationHub\HubMailConfigurator::class, function ($mock) {
        $mock->shouldReceive('sendHtml')->once();
    });

    $this->actingAs($admin, 'admin')
        ->get(route('contact.submissions.show', $id))
        ->assertOk()
        ->assertSee('Chidi Okeke')
        ->assertSee('Family prayer');

    $this->assertDatabaseHas('contact_submissions', [
        'id' => $id,
        'status' => 'read',
    ]);

    $this->actingAs($admin, 'admin')->post(route('contact.submissions.reply', $id), [
        'reply_subject' => 'Re: Family prayer',
        'reply_body' => 'We are praying with you and your family.',
    ])->assertRedirect();

    $this->assertDatabaseHas('contact_submissions', [
        'id' => $id,
        'status' => 'replied',
        'reply_subject' => 'Re: Family prayer',
    ]);
});

test('communications officer can save newsletter draft', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $this->actingAs($admin, 'admin')->post(route('communication-hub.newsletter-drafts.store'), [
        'title' => 'July Newsletter',
        'sections_json' => json_encode([['type' => 'hero', 'title' => 'Hero', 'content' => 'Welcome']]),
    ])->assertRedirect();

    $this->assertDatabaseHas('newsletter_drafts', ['title' => 'July Newsletter', 'status' => 'draft']);
});

test('communications officer can mark hub notification read', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $notificationId = DB::table('notification_center')->insertGetId([
        'admin_id' => $admin->id,
        'category' => 'general',
        'title' => 'Test alert',
        'body' => 'Something happened',
        'priority' => 'normal',
        'is_read' => false,
        'is_archived' => false,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('communication-hub.notifications.read', $notificationId))
        ->assertRedirect();

    $this->assertDatabaseHas('notification_center', ['id' => $notificationId, 'is_read' => true]);
});

test('communications officer can toggle newsletter subscriber status', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $subscriberId = DB::table('site_newsletter_subscribers')->insertGetId([
        'email' => 'subscriber@example.com',
        'source' => 'footer',
        'status' => 'active',
        'subscribed_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('newsletter-subscribers.toggle-status', $subscriberId))
        ->assertRedirect();

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'id' => $subscriberId,
        'status' => 'unsubscribed',
    ]);
});

test('communications officer can email selected newsletter subscribers', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $subscriberId = DB::table('site_newsletter_subscribers')->insertGetId([
        'email' => 'reader@example.com',
        'source' => 'footer',
        'status' => 'active',
        'subscribed_at' => now(),
        'updated_at' => now(),
    ]);

    $this->mock(\App\Services\CommunicationHub\HubMailConfigurator::class, function ($mock) {
        $mock->shouldReceive('emailSettings')->andReturn(['from_email' => 'office@agikenebgu.com']);
        $mock->shouldReceive('sendHtml')->once();
    });

    $this->actingAs($admin, 'admin')->post(route('newsletter-subscribers.send'), [
        'audience' => 'selected',
        'subscriber_ids' => [$subscriberId],
        'subject' => 'July Church News',
        'body' => 'Blessings from AG Ikenebgu this month.',
    ])->assertRedirect();

    $this->assertDatabaseHas('email_history', [
        'recipient' => 'reader@example.com',
        'subject' => 'July Church News',
        'status' => 'sent',
        'recipient_group' => 'selected',
    ]);
});

test('ss teacher cannot access communication hub', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('communication-hub.dashboard'))->assertForbidden();
});

test('communications officer can view sms center on laravel', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('sms_logs')->insert([
        'recipient_phone' => '2348012345678',
        'recipient_name' => 'Test Member',
        'message_body' => 'Hello from SMS Center',
        'provider' => 'termii',
        'status' => 'sent',
        'sent_at' => now(),
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.sms-center.index'));

    $response->assertOk();
    $response->assertSee('SMS Center');
    $response->assertSee('2348012345678');
    $response->assertSee('Hello from SMS Center');
});

test('communications officer can compose sms from sms center', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('communication_settings')->insertOrIgnore([
        'id' => 1,
        'sms_enabled' => true,
        'sms_provider' => 'termii',
        'sms_sender_id' => 'AGIKENEGBU',
        'sms_api_key' => 'test-key',
    ]);
    DB::table('communication_settings')->where('id', 1)->update([
        'sms_enabled' => true,
        'sms_provider' => 'termii',
        'sms_sender_id' => 'AGIKENEGBU',
        'sms_api_key' => 'test-key',
    ]);

    \Illuminate\Support\Facades\Http::fake([
        'api.ng.termii.com/*' => \Illuminate\Support\Facades\Http::response(['message_id' => 'msg-1'], 200),
    ]);

    $this->actingAs($admin, 'admin')->post(route('communication-hub.sms-center.compose'), [
        'phone' => '08012345678',
        'name' => 'Guest',
        'message' => 'Welcome to AG Ikenebgu',
        'priority' => 'normal',
    ])->assertRedirect(route('communication-hub.sms-center.index'));

    $this->assertDatabaseHas('sms_logs', [
        'recipient_phone' => '2348012345678',
        'message_body' => 'Welcome to AG Ikenebgu',
        'status' => 'sent',
        'provider' => 'termii',
    ]);
});

test('communications officer can view birthday calendar on laravel', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('members')->insert([
        'member_code' => 'M-BDAY-1',
        'full_name' => 'Birthday Test Member',
        'date_of_birth' => now()->format('Y-m-d'),
        'email' => 'birthday@example.com',
        'phone' => '08012345678',
        'address_line1' => '1 Test Street',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'General',
        'status' => 'active',
        'joined_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('communication-hub.birthdays.index'));

    $response->assertOk();
    $response->assertSee('Birthday Calendar');
    $response->assertSee('Birthday Test Member');
    $response->assertSee('Email / SMS settings');
});

test('hub mail configurator applies hostinger smtp from email settings table', function () {
    DB::table('email_settings')->insertOrIgnore([
        'id' => 1,
        'provider' => 'smtp',
        'from_email' => 'office@agcikenegbu.org',
        'from_name' => 'AG Ikenebgu',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_user' => 'office@agcikenegbu.org',
        'smtp_pass' => 'secret-pass',
    ]);
    DB::table('email_settings')->where('id', 1)->update([
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_encryption' => 'ssl',
        'from_email' => 'office@agcikenegbu.org',
    ]);

    app(\App\Services\CommunicationHub\HubMailConfigurator::class)->configure();

    expect(config('mail.default'))->toBe('communication_hub');
    expect(config('mail.mailers.communication_hub.scheme'))->toBe('smtps');
    expect(config('mail.from.address'))->toBe('office@agcikenegbu.org');
});

test('communications officer can save email and sms hub settings', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('communication_settings')->insertOrIgnore(['id' => 1]);
    DB::table('email_settings')->insertOrIgnore([
        'id' => 1,
        'provider' => 'smtp',
        'from_email' => 'noreply@agikenebgu.com',
        'from_name' => 'Assemblies of God Ikenegbu',
    ]);

    $this->actingAs($admin, 'admin')->put(route('communication-hub.settings.update'), [
        'default_channel' => 'email',
        'from_email' => 'office@agikenebgu.com',
        'from_name' => 'AG Ikenebgu Office',
        'smtp_host' => 'smtp.mail.test',
        'smtp_port' => 587,
        'smtp_user' => 'smtp-user',
        'smtp_pass' => 'secret-pass',
        'smtp_encryption' => 'tls',
        'sms_enabled' => '1',
        'sms_provider' => 'termii',
        'sms_sender_id' => 'AGIKENEGBU',
        'sms_api_key' => 'test-key',
        'birthday_auto_email_enabled' => '1',
        'birthday_auto_sms_enabled' => '0',
        'rate_limit_per_minute' => 120,
        'church_website' => 'agcikenegbu.org',
    ])->assertRedirect(route('communication-hub.settings.edit'));

    $this->assertDatabaseHas('email_settings', [
        'id' => 1,
        'from_email' => 'office@agikenebgu.com',
        'smtp_host' => 'smtp.mail.test',
        'smtp_user' => 'smtp-user',
        'church_website' => 'https://agcikenegbu.org',
    ]);

    $this->assertDatabaseHas('communication_settings', [
        'id' => 1,
        'sms_enabled' => 1,
        'sms_sender_id' => 'AGIKENEGBU',
        'birthday_auto_sms_enabled' => 0,
    ]);
});
