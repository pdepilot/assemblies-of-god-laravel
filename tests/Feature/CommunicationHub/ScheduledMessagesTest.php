<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('authenticated admin can view scheduled messages', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('scheduled_messages')->insert([
        'message_code' => 'SCHTEST001',
        'channel' => 'email',
        'subject' => 'Sunday Reminder',
        'body_html' => '<p>See you Sunday</p>',
        'body_text' => 'See you Sunday',
        'recipient_group_key' => 'all_members',
        'priority' => 'normal',
        'recurrence' => 'none',
        'scheduled_at' => now()->addDay(),
        'next_run_at' => now()->addDay(),
        'status' => 'scheduled',
        'created_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.scheduled.index'))
        ->assertOk()
        ->assertSee('Scheduled Messages')
        ->assertSee('Sunday Reminder');
});

test('authenticated admin can schedule a message', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $when = now()->addDays(2)->format('Y-m-d H:i:s');

    $this->actingAs($admin, 'admin')
        ->post(route('communication-hub.scheduled.store'), [
            'channel' => 'sms',
            'subject' => 'Prayer Night',
            'body_html' => 'Join us for prayer night',
            'recipient_group_key' => 'all_members',
            'priority' => 'high',
            'recurrence' => 'none',
            'scheduled_at' => $when,
        ])
        ->assertRedirect(route('communication-hub.scheduled.index'));

    $this->assertDatabaseHas('scheduled_messages', [
        'channel' => 'sms',
        'subject' => 'Prayer Night',
        'status' => 'scheduled',
        'created_by' => $admin->id,
    ]);
});
