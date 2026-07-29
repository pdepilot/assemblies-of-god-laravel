<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('authenticated admin can view automation rules', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('automation_rules')->insert([
        'rule_key' => 'member_birthday_email',
        'name' => 'Birthday Email',
        'trigger_event' => 'member.birthday',
        'actions_json' => json_encode([['type' => 'send', 'channel' => 'email', 'template_slug' => 'birthday_message']]),
        'channel' => 'email',
        'template_slug' => 'birthday_message',
        'priority' => 10,
        'is_enabled' => 0,
        'is_system' => 1,
        'run_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.automation.index'))
        ->assertOk()
        ->assertSee('Automation Rules')
        ->assertSee('Birthday Email');
});

test('authenticated admin can toggle an automation rule', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $id = (int) DB::table('automation_rules')->insertGetId([
        'rule_key' => 'member_birthday_sms',
        'name' => 'Birthday SMS',
        'trigger_event' => 'member.birthday',
        'actions_json' => json_encode([['type' => 'send', 'channel' => 'sms', 'template_slug' => 'birthday_sms']]),
        'channel' => 'sms',
        'template_slug' => 'birthday_sms',
        'priority' => 20,
        'is_enabled' => 0,
        'is_system' => 1,
        'run_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('communication-hub.automation.toggle', $id), ['is_enabled' => '1'])
        ->assertRedirect();

    expect((int) DB::table('automation_rules')->where('id', $id)->value('is_enabled'))->toBe(1);
});
