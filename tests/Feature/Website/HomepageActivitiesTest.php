<?php

use App\Models\Admin;

test('page manager activities row opens the activities editor', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'activities'))
        ->assertRedirect(route('website.activities.edit'));
});

test('page manager shows homepage activities editor', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.index'))
        ->assertOk()
        ->assertSee('Homepage Activities')
        ->assertSee('Activity card 1')
        ->assertSee('Sunday Worship');
});

test('homepage page manager shows activity card fields', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'home'))
        ->assertOk()
        ->assertSee('Activities section')
        ->assertSee('Sunday Worship');
});

test('content editor can edit homepage activity cards', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.activities.edit'))
        ->assertOk()
        ->assertSee('Activities')
        ->assertSee('Sunday Worship');

    $this->actingAs($admin, 'admin')
        ->put(route('website.activities.update'), [
            'activities' => [
                [
                    'id' => 0,
                    'title' => 'Street Evangelism',
                    'description' => 'We take the Gospel to our neighbourhoods every month.',
                    'icon_class' => 'fa-globe',
                    'meeting_schedule' => 'Last Saturday 9:00 AM',
                    'read_more_url' => 'contact',
                    'is_published' => '1',
                ],
                [
                    'id' => 0,
                    'title' => 'Hidden Draft Card',
                    'description' => 'Should not appear on the homepage.',
                    'icon_class' => 'fa-book',
                    'meeting_schedule' => '',
                    'read_more_url' => '',
                    'is_published' => '0',
                ],
            ],
        ])
        ->assertRedirect(route('website.activities.edit'));

    $this->assertDatabaseHas('church_activities', [
        'title' => 'Street Evangelism',
        'is_published' => 1,
    ]);

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('Street Evangelism');
    $home->assertSee('Last Saturday 9:00 AM');
    $home->assertSee('We take the Gospel to our neighbourhoods every month.');
    $home->assertDontSee('Hidden Draft Card');
    $home->assertDontSee('Lift your voice in praise');
});

test('ss teacher cannot edit homepage activities', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('website.activities.edit'))->assertForbidden();
});
