<?php

use App\Models\Admin;

test('page manager worship row opens the worship editor', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'worship'))
        ->assertRedirect(route('website.worship.edit'));

    $this->actingAs($admin, 'admin')
        ->get('/portal/website/pages')
        ->assertRedirect(route('website.pages.index'));
});

test('homepage page manager shows worship programme fields', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'home'))
        ->assertOk()
        ->assertSee('Sunday Worship Services')
        ->assertSee('Church location map')
        ->assertSee('Map search');
});

test('content editor can edit homepage worship programmes', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.worship.edit'))
        ->assertOk()
        ->assertSee('Our Worship')
        ->assertSee('Sunday Worship Services');

    $this->actingAs($admin, 'admin')
        ->put(route('website.worship.update'), [
            'programs' => [
                [
                    'day' => 'Every Sunday',
                    'icon' => 'fa-calendar-day',
                    'title' => 'Sunday Combined Service',
                    'time_primary' => '9:00 AM',
                    'note_primary' => 'One gathering for the whole church',
                    'time_secondary' => '',
                    'note_secondary' => '',
                    'body' => 'Children stay with families during the service.',
                    'cta_label' => '',
                    'cta_url' => '',
                ],
                [
                    'day' => 'Every Thursday',
                    'icon' => 'fa-bible',
                    'title' => 'Night of Teaching',
                    'time_primary' => '5:30 PM',
                    'note_primary' => '',
                    'time_secondary' => '',
                    'note_secondary' => '',
                    'body' => 'Verse-by-verse Bible study.',
                    'cta_label' => 'Study notes',
                    'cta_url' => 'blog',
                ],
            ],
            'worship_map_query' => '12 Church Road, Owerri',
        ])
        ->assertRedirect(route('website.worship.edit'));

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('Sunday Combined Service');
    $home->assertSee('9:00 AM');
    $home->assertSee('Night of Teaching');
    $home->assertSee('Every Thursday');
    $home->assertDontSee('Midweek Bible Study');
    $home->assertSee(rawurlencode('12 Church Road, Owerri'), false);
});

test('ss teacher cannot access worship programme editor', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('website.worship.edit'))->assertForbidden();
});
