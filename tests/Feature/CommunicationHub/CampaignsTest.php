<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('authenticated admin can view campaign manager', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    DB::table('communication_campaigns')->insert([
        'campaign_code' => 'CMPTEST001',
        'name' => 'Easter Outreach',
        'campaign_type' => 'email',
        'status' => 'draft',
        'audience_group_key' => 'all_members',
        'subject' => 'Join us',
        'body_html' => '<p>Hello</p>',
        'body_text' => 'Hello',
        'created_by' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.campaigns.index'))
        ->assertOk()
        ->assertSee('Campaign Manager')
        ->assertSee('Easter Outreach');
});

test('authenticated admin can create a campaign', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $this->actingAs($admin, 'admin')
        ->post(route('communication-hub.campaigns.store'), [
            'name' => 'Youth Rally Invite',
            'campaign_type' => 'sms',
            'status' => 'draft',
            'audience_group_key' => 'youth',
            'subject' => 'Youth Rally',
            'body_html' => 'See you at the rally',
        ])
        ->assertRedirect(route('communication-hub.campaigns.index'));

    $this->assertDatabaseHas('communication_campaigns', [
        'name' => 'Youth Rally Invite',
        'campaign_type' => 'sms',
        'status' => 'draft',
        'created_by' => $admin->id,
    ]);
});
