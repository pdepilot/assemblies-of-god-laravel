<?php

use App\Models\Admin;

test('admin session times out after inactivity', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_last_activity' => time() - 1900])
        ->get(route('dashboard'))
        ->assertRedirect(route('login', ['reason' => 'inactivity']));

    $this->assertGuest('admin');
});

test('admin session ping reports remaining time without extending idle clock', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $started = time() - 60;

    $response = $this->actingAs($admin, 'admin')
        ->withSession(['admin_last_activity' => $started])
        ->getJson(route('admin.session', ['action' => 'ping']));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('authenticated', true);

    expect((int) $response->json('remaining'))->toBeLessThanOrEqual(1740);
    expect((int) session('admin_last_activity'))->toBe($started);
});

test('admin can extend idle session', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $started = time() - 600;

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_last_activity' => $started])
        ->post(route('admin.session'), ['action' => 'extend'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect((int) session('admin_last_activity'))->toBeGreaterThan($started);
});

test('login page shows inactivity message', function () {
    $this->get(route('login', ['reason' => 'inactivity']))
        ->assertOk()
        ->assertSee('logged out due to inactivity');
});
