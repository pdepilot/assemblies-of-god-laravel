<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('admin can view activity logs and export csv without leaving laravel', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('security_logs')->insert([
        'event_type' => 'login_success',
        'severity' => 'info',
        'admin_id' => $admin->id,
        'device_fingerprint' => null,
        'ip_address' => '203.0.113.50',
        'user_agent' => 'PHPUnit',
        'message' => 'Administrator logged in successfully.',
        'metadata' => null,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('security.activity-logs.index'))
        ->assertOk()
        ->assertSee('Activity Logs')
        ->assertSee('Login Success')
        ->assertSee('Administrator logged in successfully.');

    $this->actingAs($admin, 'admin')
        ->get(route('security.activity-logs.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->assertSee('Login Success');
});
