<?php

use App\Models\Admin;
use App\Models\WebsitePromotion;
use Illuminate\Support\Facades\DB;

test('content editor can create an active promotion banner that appears on homepage load', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.promotions.index'))
        ->assertOk()
        ->assertSee('Promotion Banner');

    $this->actingAs($admin, 'admin')
        ->post(route('website.promotions.store'), [
            'title' => 'Night of Glory Crusade',
            'eyebrow' => 'This Friday',
            'body' => 'Join us for worship, prayer, and the Word.',
            'cta_label' => 'View events',
            'cta_url' => 'event',
            'sort_order' => 20,
            'is_active' => '1',
            'show_every_visit' => '1',
        ])
        ->assertRedirect(route('website.promotions.index'));

    $this->assertDatabaseHas('website_promotions', [
        'title' => 'Night of Glory Crusade',
        'is_active' => 1,
    ]);

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('agPromoBanner', false);
    $home->assertSee('agPromoDismissTpl', false);
    $home->assertSee('Night of Glory Crusade');
    $home->assertSee('This Friday');
    $home->assertSee('View events');
    $home->assertSee(url('/event'), false);
});

test('inactive and ended promotions do not appear on the public homepage', function () {
    /** @var \Tests\TestCase $this */
    DB::table('website_promotions')->insert([
        'title' => 'Hidden Inactive Promo',
        'eyebrow' => null,
        'body' => null,
        'image_path' => null,
        'cta_label' => null,
        'cta_url' => null,
        'starts_at' => null,
        'ends_at' => null,
        'sort_order' => 50,
        'is_active' => false,
        'show_every_visit' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('website_promotions')->insert([
        'title' => 'Already Ended Promo',
        'eyebrow' => null,
        'body' => null,
        'image_path' => null,
        'cta_label' => null,
        'cta_url' => null,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDay(),
        'sort_order' => 80,
        'is_active' => true,
        'show_every_visit' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertDontSee('Hidden Inactive Promo');
    $home->assertDontSee('Already Ended Promo');
    $home->assertDontSee('agPromoBanner', false);
});

test('upcoming programme dates still show the active banner on load', function () {
    /** @var \Tests\TestCase $this */
    DB::table('website_promotions')->insert([
        'title' => 'Cross Over Night',
        'eyebrow' => null,
        'body' => null,
        'image_path' => null,
        'cta_label' => 'Learn more',
        'cta_url' => 'event',
        'starts_at' => now()->addDays(6),
        'ends_at' => now()->addDays(7),
        'sort_order' => 10,
        'is_active' => true,
        'show_every_visit' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('agPromoBanner', false);
    $home->assertSee('Cross Over Night');
});

test('promotion banner does not appear on inner public pages', function () {
    /** @var \Tests\TestCase $this */
    WebsitePromotion::query()->create([
        'title' => 'Cross Over Night Inner Page Check',
        'is_active' => true,
        'show_every_visit' => true,
        'sort_order' => 10,
    ]);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('agPromoBanner', false)
        ->assertSee('Cross Over Night Inner Page Check');

    $this->get(route('public.about'))
        ->assertOk()
        ->assertDontSee('agPromoBanner', false)
        ->assertDontSee('Cross Over Night Inner Page Check');

    $this->get(route('public.event'))
        ->assertOk()
        ->assertDontSee('agPromoBanner', false);
});

test('highest priority active promotion is the one shown on load', function () {
    /** @var \Tests\TestCase $this */
    WebsitePromotion::query()->create([
        'title' => 'Lower Priority Programme',
        'is_active' => true,
        'show_every_visit' => true,
        'sort_order' => 1,
    ]);
    WebsitePromotion::query()->create([
        'title' => 'Higher Priority Programme',
        'is_active' => true,
        'show_every_visit' => true,
        'sort_order' => 9,
    ]);

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('Higher Priority Programme');
    $home->assertDontSee('Lower Priority Programme');
});

test('finance role cannot manage promotion banners', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.promotions.index'))
        ->assertForbidden();
});
