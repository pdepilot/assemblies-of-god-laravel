<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('authenticated admin can view recipient groups', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    if (Schema::hasTable('recipient_groups')) {
        DB::table('recipient_groups')->insert([
            'group_key' => 'custom_elders',
            'name' => 'Custom Elders',
            'description' => 'Custom audience',
            'group_type' => 'smart',
            'is_system' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.recipients.index'))
        ->assertOk()
        ->assertSee('Recipient Groups')
        ->assertSee('All Members')
        ->assertSee('all_members')
        ->assertSee('System');

    if (Schema::hasTable('recipient_groups')) {
        $response->assertSee('Custom Elders')->assertSee('custom_elders');
    }
});
