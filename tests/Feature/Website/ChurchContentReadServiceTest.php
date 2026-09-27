<?php

use App\Services\Website\ChurchContentReadService;
use Illuminate\Support\Facades\DB;

test('getSection about_page returns saved about_page merged with defaults', function () {
    DB::table('ag_site_content')->insert([
        'section_key' => 'hero',
        'content_json' => json_encode(['headline' => 'Unrelated hero row']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('ag_site_content')->insert([
        'section_key' => 'about_page',
        'content_json' => json_encode([
            'title' => 'Saved About Page Title',
            'intro' => 'Saved about page intro.',
            'gallery' => [
                ['image' => 'images/custom-about.jpg', 'alt' => 'Custom gallery'],
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $section = app(ChurchContentReadService::class)->getSection('about_page');

    expect($section['title'])->toBe('Saved About Page Title');
    expect($section['intro'])->toBe('Saved about page intro.');
    expect($section['gallery'][0]['image'])->toBe('images/custom-about.jpg');
    expect($section['hero']['title'])->toBe('About Us');
    expect($section)->not->toHaveKey('headline');
});

test('getSection about_page inherits shared fields from homepage_about when about_page is missing', function () {
    DB::table('ag_site_content')->insert([
        'section_key' => 'homepage_about',
        'content_json' => json_encode([
            'eyebrow' => 'Inherited Eyebrow',
            'title' => 'Inherited About Body Title',
            'intro' => 'Inherited intro for about page.',
            'gallery' => [
                ['image' => 'images/rev1.jpg', 'alt' => 'Worship'],
            ],
            'scripture_banner' => [
                'cta_label' => 'Should not copy onto about_page',
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $section = app(ChurchContentReadService::class)->getSection('about_page');

    expect($section['eyebrow'])->toBe('Inherited Eyebrow');
    expect($section['title'])->toBe('Inherited About Body Title');
    expect($section['intro'])->toBe('Inherited intro for about page.');
    expect($section['gallery'][0]['image'])->toBe('images/rev1.jpg');
    expect($section['hero']['title'])->toBe('About Us');
    expect($section)->not->toHaveKey('scripture_banner');
});

test('getSection about_page does not inherit when about_page row exists', function () {
    DB::table('ag_site_content')->insert([
        'section_key' => 'homepage_about',
        'content_json' => json_encode(['title' => 'Homepage Title Only']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('ag_site_content')->insert([
        'section_key' => 'about_page',
        'content_json' => json_encode(['title' => 'Dedicated About Title']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $section = app(ChurchContentReadService::class)->getSection('about_page');

    expect($section['title'])->toBe('Dedicated About Title');
});

test('getSection homepage_about still returns that section without loading about_page', function () {
    DB::table('ag_site_content')->insert([
        'section_key' => 'homepage_about',
        'content_json' => json_encode(['title' => 'Homepage About Only']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $section = app(ChurchContentReadService::class)->getSection('homepage_about');

    expect($section['title'])->toBe('Homepage About Only');
    expect($section['scripture_banner']['cta_url'])->toBe('about');
});
