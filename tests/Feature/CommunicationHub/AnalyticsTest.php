<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('authenticated admin can view communication analytics', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    DB::table('email_history')->insert([
        [
            'tracking_token' => Str::random(64),
            'subject' => 'Opened message',
            'recipient' => 'a@example.com',
            'template_slug' => 'welcome_email',
            'status' => 'sent',
            'opened' => 1,
            'clicked' => 1,
            'created_at' => now(),
        ],
        [
            'tracking_token' => Str::random(64),
            'subject' => 'Delivered only',
            'recipient' => 'b@example.com',
            'template_slug' => 'welcome_email',
            'status' => 'sent',
            'opened' => 0,
            'clicked' => 0,
            'created_at' => now(),
        ],
        [
            'tracking_token' => Str::random(64),
            'subject' => 'Failed message',
            'recipient' => 'c@example.com',
            'template_slug' => 'alert_email',
            'status' => 'failed',
            'opened' => 0,
            'clicked' => 0,
            'created_at' => now(),
        ],
    ]);

    if (Schema::hasTable('sms_logs')) {
        DB::table('sms_logs')->insert([
            'recipient_phone' => '08012345678',
            'message_body' => 'Analytics SMS',
            'provider' => 'termii',
            'status' => 'sent',
            'created_at' => now(),
        ]);
    }

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.analytics.index'))
        ->assertOk()
        ->assertSee('Communication Analytics')
        ->assertSee('Open Rate')
        ->assertSee('Monthly Trends')
        ->assertSee('Most Used Templates')
        ->assertSee('welcome_email')
        ->assertSee(now()->format('Y-m'));
});
