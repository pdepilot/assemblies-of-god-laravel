<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('authenticated admin can view communication queue', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('sms_queue')->insert([
        'recipient_phone' => '08012345678',
        'recipient_name' => 'Test User',
        'message_body' => 'Queue test SMS',
        'provider' => 'termii',
        'priority' => 'normal',
        'scheduled_at' => now()->subMinute(),
        'status' => 'pending',
        'attempts' => 0,
        'sent_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.queue.index'))
        ->assertOk()
        ->assertSee('Communication Queue')
        ->assertSee('SMS Pending')
        ->assertSee('08012345678')
        ->assertSee('Showing');
});

test('recent queue jobs are paginated', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $rows = [];
    for ($i = 1; $i <= 16; $i++) {
        $rows[] = [
            'recipient_phone' => sprintf('0801234%04d', $i),
            'recipient_name' => "User {$i}",
            'message_body' => "Queue SMS {$i}",
            'provider' => 'termii',
            'priority' => 'normal',
            'scheduled_at' => now()->subMinutes($i),
            'status' => 'pending',
            'attempts' => 0,
            'sent_by' => $admin->id,
            'created_at' => now()->subMinutes($i),
            'updated_at' => now(),
        ];
    }
    DB::table('sms_queue')->insert($rows);

    // Newest first (i=1); page 1 has 15 items, page 2 has the oldest (i=16).
    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.queue.index'))
        ->assertOk()
        ->assertSee('Showing 1–15 of 16')
        ->assertSee('08012340001')
        ->assertDontSee('08012340016');

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.queue.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Showing 16–16 of 16')
        ->assertSee('08012340016');
});

test('authenticated admin can process communication queues', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('sms_queue')->insert([
        'recipient_phone' => '08099998888',
        'message_body' => 'Process me',
        'provider' => 'termii',
        'priority' => 'normal',
        'scheduled_at' => now()->subMinute(),
        'status' => 'pending',
        'attempts' => 0,
        'sent_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // SMS may fail without live gateway credentials; process should still complete.
    $this->actingAs($admin, 'admin')
        ->post(route('communication-hub.queue.process'))
        ->assertRedirect(route('communication-hub.queue.index'))
        ->assertSessionHas('status');
});

test('authenticated admin can delete all failed queue jobs', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('sms_queue')->insert([
        [
            'recipient_phone' => '08011112222',
            'message_body' => 'Keep pending',
            'provider' => 'termii',
            'priority' => 'normal',
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
            'attempts' => 0,
            'sent_by' => $admin->id,
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'recipient_phone' => '08033334444',
            'message_body' => 'Delete failed',
            'provider' => 'termii',
            'priority' => 'normal',
            'scheduled_at' => now()->subMinute(),
            'status' => 'failed',
            'attempts' => 1,
            'sent_by' => $admin->id,
            'error_message' => 'Gateway error',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    if (Schema::hasTable('email_queue')) {
        DB::table('email_queue')->insert([
            'recipient' => 'failed@example.com',
            'subject' => 'Failed email',
            'body_html' => '<p>Fail</p>',
            'scheduled_at' => now()->subMinute(),
            'status' => 'failed',
            'attempts' => 1,
            'error_message' => 'SMTP error',
            'created_at' => now(),
        ]);
    }

    $this->actingAs($admin, 'admin')
        ->delete(route('communication-hub.queue.destroy-failed'))
        ->assertRedirect(route('communication-hub.queue.index'))
        ->assertSessionHas('status');

    expect(DB::table('sms_queue')->where('status', 'failed')->count())->toBe(0);
    expect(DB::table('sms_queue')->where('status', 'pending')->count())->toBe(1);

    if (Schema::hasTable('email_queue')) {
        expect(DB::table('email_queue')->where('status', 'failed')->count())->toBe(0);
    }
});
