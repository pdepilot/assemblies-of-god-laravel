<?php

use App\Models\Admin;

test('sdtg login screen can be rendered', function () {
    $this->get('/admin/sdtg/login')->assertOk();
});

test('sdtg-only user is blocked from ag login', function () {
    $admin = Admin::factory()->sdtgOnly()->create([
        'email' => 'sdtg-only@example.com',
    ]);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('ag-only user is blocked from sdtg login', function () {
    $admin = Admin::factory()->agOnly()->create([
        'email' => 'ag-only@example.com',
    ]);

    $this->post('/admin/sdtg/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('sdtg');
});

test('dual-platform user can authenticate through both guards', function () {
    $admin = Admin::factory()->bothPlatforms()->create([
        'email' => 'both@example.com',
        'role' => 'sdtg_administrator',
    ]);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated('admin');

    $this->post('/admin/sdtg/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('sdtg.dashboard', absolute: false));

    $this->assertAuthenticated('admin');
    $this->assertAuthenticated('sdtg');
});

test('sdtg logout does not break ag authentication', function () {
    $admin = Admin::factory()->bothPlatforms()->create([
        'email' => 'session-iso@example.com',
        'role' => 'sdtg_administrator',
    ]);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->post('/admin/sdtg/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('sdtg.dashboard', absolute: false));

    $this->assertAuthenticated('admin');
    $this->assertAuthenticated('sdtg');

    $this->post('/admin/sdtg/logout')->assertRedirect('/admin/sdtg/login');

    $this->assertGuest('sdtg');
    $this->assertAuthenticated('admin');
});

test('unauthenticated sdtg routes redirect to sdtg login', function () {
    $this->get('/admin/sdtg')->assertRedirect('/admin/sdtg/login');
});

test('sdtg-only user can authenticate through sdtg login', function () {
    $admin = Admin::factory()->sdtgOnly()->create([
        'email' => 'sdtg-ok@example.com',
        'role' => 'sdtg_administrator',
    ]);

    $this->post('/admin/sdtg/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('sdtg.dashboard', absolute: false));

    $this->assertAuthenticated('sdtg');
    $this->assertGuest('admin');
});
