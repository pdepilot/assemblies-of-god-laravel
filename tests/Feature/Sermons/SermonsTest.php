<?php

use App\Models\Admin;

test('media administrator can view sermons dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'media_administrator']);

    $response = $this->actingAs($admin, 'admin')->get(route('sermon.dashboard'));

    $response->assertOk();
    $response->assertSee('Sermons');
    $response->assertSee('Broadcasts');
});

test('media administrator can create sermon', function () {
    $admin = Admin::factory()->create(['role' => 'media_administrator']);

    $this->actingAs($admin, 'admin')->post(route('sermon.sermons.store'), [
        'title' => 'Faith That Moves Mountains',
        'sermon_date' => '2026-07-20',
        'minister_name' => 'Rev. Bethel Nwanebu',
        'sermon_type' => 'audio',
        'status' => 'draft',
    ])->assertRedirect();

    $this->assertDatabaseHas('sermons', [
        'title' => 'Faith That Moves Mountains',
        'minister_name' => 'Rev. Bethel Nwanebu',
        'status' => 'draft',
    ]);
});

test('ss teacher cannot access sermons module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('sermon.dashboard'))->assertForbidden();
});
