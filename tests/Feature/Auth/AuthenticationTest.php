<?php

use App\Models\Admin;

test('login screen can be rendered', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->get('/portal/login');

    $response->assertStatus(200);
});

test('admins can authenticate using the login screen', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create([
        'email' => 'admin@example.com',
    ]);

    $response = $this->post('/portal/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('admin');
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('admins with argon2 legacy password hash can log in', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create([
        'email' => 'legacy@example.com',
        'password_hash' => password_hash('legacy-pass', PASSWORD_ARGON2ID),
    ]);

    $this->post('/portal/login', [
        'email' => $admin->email,
        'password' => 'legacy-pass',
    ])->assertRedirect(route('dashboard', absolute: false));

    $admin->refresh();
    expect(str_starts_with((string) $admin->password_hash, '$2y$'))->toBeTrue();
});

test('admins can not authenticate with invalid password', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create();

    $this->post('/portal/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('admin');
});

test('admins can logout', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->post('/admin/logout');

    $this->assertGuest('admin');
    $response->assertRedirect('/portal/login');
});
