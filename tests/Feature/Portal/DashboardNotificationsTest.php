<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('dashboard bootstrap handler returns json for authenticated admin', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->getJson('/admin/handlers/dashboard-handler?action=bootstrap');

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonStructure([
        'overview' => ['ag', 'sunday_school', 'finance'],
        'charts',
        'activity',
        'notifications',
        'unread_count',
        'latest_id',
        'last_read_id',
        'csrf_token',
    ]);
});

test('dashboard bootstrap handler is also available at the former portal path', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->getJson('/portal/handlers/dashboard-handler?action=bootstrap')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('dashboard notifications handler returns json for authenticated admin', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('security_logs')->insert([
        'admin_id' => $admin->id,
        'event_type' => 'member_created',
        'severity' => 'info',
        'message' => 'New member John Doe was registered.',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson('/admin/handlers/dashboard-handler?action=notifications');

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonStructure(['notifications', 'unread_count', 'latest_id', 'last_read_id', 'csrf_token']);
    $response->assertJsonFragment(['text' => 'New member John Doe was registered.']);
});

test('dashboard notifications handler marks notifications as read', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $logId = DB::table('security_logs')->insertGetId([
        'admin_id' => $admin->id,
        'event_type' => 'visitor_created',
        'severity' => 'info',
        'message' => 'Visitor registered.',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->postJson('/admin/handlers/dashboard-handler', [
        'action' => 'mark_notifications_read',
        'last_log_id' => $logId,
    ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin_notification_reads', [
        'admin_id' => $admin->id,
        'last_read_log_id' => $logId,
    ]);
});

test('dashboard notifications include hub items and support since_id polling', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $firstId = DB::table('security_logs')->insertGetId([
        'admin_id' => $admin->id,
        'event_type' => 'donation_recorded',
        'severity' => 'info',
        'message' => 'Donation recorded.',
        'created_at' => now()->subMinute(),
    ]);

    $secondId = DB::table('security_logs')->insertGetId([
        'admin_id' => $admin->id,
        'event_type' => 'erp_alert',
        'severity' => 'warning',
        'message' => 'Unposted receipts crossed threshold.',
        'created_at' => now(),
    ]);

    if (Schema::hasTable('notification_center')) {
        DB::table('notification_center')->insert([
            'admin_id' => $admin->id,
            'category' => 'general',
            'title' => 'Hub ping',
            'body' => 'Communication hub message',
            'priority' => 'normal',
            'is_read' => false,
            'is_archived' => false,
            'created_at' => now(),
        ]);
    }

    $full = $this->actingAs($admin, 'admin')->getJson('/admin/handlers/dashboard-handler?action=notifications');
    $full->assertOk()->assertJsonPath('success', true);
    $full->assertJsonFragment(['text' => 'Unposted receipts crossed threshold.']);
    if (Schema::hasTable('notification_center')) {
        $full->assertJsonFragment(['title' => 'Hub ping']);
    }

    $delta = $this->actingAs($admin, 'admin')->getJson('/admin/handlers/dashboard-handler?action=notifications&since_id='.$firstId);
    $delta->assertOk();
    $delta->assertJsonPath('success', true);
    $ids = collect($delta->json('notifications'))->pluck('id')->all();
    expect($ids)->toContain($secondId);
    expect($ids)->not->toContain($firstId);
});

test('public contact testimony and newsletter create dashboard notifications', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->postJson(route('public.contact.submit'), [
        'action' => 'submit',
        'inquiry_type' => 'general',
        'name' => 'Bell Contact',
        'email' => 'bell-contact@example.com',
        'subject' => 'Hello church',
        'message' => 'This is a long enough contact message for the inbox.',
        'ag_hp_trap' => '',
    ])->assertOk()->assertJsonPath('success', true);

    $this->postJson(route('public.testimony.submit'), [
        'name' => 'Bell Testimony',
        'email' => 'bell-testimony@example.com',
        'role' => 'Member',
        'testimony' => 'God has been faithful through every season of my life at church.',
        'source_page' => 'index',
    ])->assertOk()->assertJsonPath('success', true);

    $this->postJson(route('public.newsletter.subscribe'), [
        'email' => 'bell-news@example.com',
        'source' => 'footer',
        'website' => '',
    ])->assertOk()->assertJsonPath('success', true);

    $response = $this->actingAs($admin, 'admin')->getJson('/admin/handlers/dashboard-handler?action=notifications');
    $response->assertOk()->assertJsonPath('success', true);
    $response->assertJsonFragment(['title' => 'Contact Message']);
    $response->assertJsonFragment(['play_alert' => true]);
    $response->assertJsonFragment(['title' => 'New Testimony']);
    $response->assertJsonFragment(['title' => 'Newsletter']);
});
