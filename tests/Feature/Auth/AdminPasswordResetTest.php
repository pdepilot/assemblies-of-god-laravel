<?php

use App\Models\Admin;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('login page shows forgot password link', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Forgot password?')
        ->assertSee(route('password.request'), false);
});

test('guest can open forgot password form', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot Password')
        ->assertSee('Send Reset Link');
});

test('active admin receives password reset notification', function () {
    Notification::fake();

    $admin = Admin::factory()->create([
        'email' => 'reset.admin@example.com',
        'recovery_email' => 'recovery.admin@example.com',
        'is_active' => true,
        'account_status' => 'active',
    ]);

    $this->post(route('password.email'), [
        'email' => 'reset.admin@example.com',
    ])->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
});

test('unknown or suspended admin does not receive reset email but sees success', function () {
    Notification::fake();

    Admin::factory()->create([
        'email' => 'suspended.admin@example.com',
        'is_active' => false,
        'account_status' => 'suspended',
    ]);

    $this->post(route('password.email'), [
        'email' => 'missing.admin@example.com',
    ])->assertRedirect()
        ->assertSessionHas('status');

    $this->post(route('password.email'), [
        'email' => 'suspended.admin@example.com',
    ])->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('admin can reset password with valid token and sign in', function () {
    $admin = Admin::factory()->create([
        'email' => 'newpass.admin@example.com',
        'password_hash' => Hash::make('OldPassword1!'),
        'is_active' => true,
        'account_status' => 'active',
    ]);

    $token = Password::broker('admins')->createToken($admin);

    $this->get(route('password.reset', [
        'token' => $token,
        'email' => $admin->email,
    ]))->assertOk()
        ->assertSee('Reset Password');

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $admin->email,
        'password' => 'BrandNewPass9!',
        'password_confirmation' => 'BrandNewPass9!',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $admin->refresh();
    expect(Hash::check('BrandNewPass9!', $admin->password_hash))->toBeTrue();

    $this->post(route('login'), [
        'email' => 'newpass.admin@example.com',
        'password' => 'BrandNewPass9!',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($admin->fresh(), 'admin');
});
