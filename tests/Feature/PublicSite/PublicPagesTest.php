<?php

use Illuminate\Support\Facades\Http;

test('public homepage still renders with brand content', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Ikenebgu', false);
    $response->assertSee(asset('site'), false);
});

test('public about route is registered and responds', function () {
    // Bridge needs legacy host; accept either rewritten HTML or fallback page.
    $response = $this->get('/about');

    $response->assertOk();
    $response->assertSee('AG', false);
});

test('asset resolver falls back to media base when local file is missing', function () {
    $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);
    $url = $resolver->url('images/definitely-missing-file-xyz.jpg');

    expect($url)->toContain('definitely-missing-file-xyz.jpg');
    expect($url)->toStartWith(rtrim((string) config('portal.media_base'), '/'));
});

test('public homepage uses APP_URL for the same-origin API base', function () {
    config(['app.url' => 'https://agcikenegbu.org']);
    \Illuminate\Support\Facades\URL::forceRootUrl('https://agcikenegbu.org');
    \Illuminate\Support\Facades\URL::forceScheme('https');
    config([
        'portal.legacy_api_base' => 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/api',
        'portal.media_base' => 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE',
        'portal.legacy_admin_base' => 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/portal',
    ]);

    $response = $this->get('/');
    $html = $response->getContent();

    $response->assertOk();
    expect($html)->toMatch('/data-api-base="https?:\/\/agcikenegbu\.org\/api"/');
    expect($html)->not->toContain('localhost/AG_IKENEGBU_CHURCH_WEBSITE');
    $response->assertSee('AGC Ikenegbu | Assemblies of God Church, Owerri', false);
    $response->assertSee('Ikenegbu, Owerri, Imo State', false);
    $response->assertDontSee('Edited SEO Home Title', false);
});

test('asset resolver rewrites stored localhost media URLs to the Laravel origin', function () {
    config(['app.url' => 'https://agcikenegbu.org']);
    \Illuminate\Support\Facades\URL::forceRootUrl('https://agcikenegbu.org');
    \Illuminate\Support\Facades\URL::forceScheme('https');
    config([
        'portal.media_base' => 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE',
        'portal.legacy_admin_base' => 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/portal',
    ]);

    $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);

    $missing = $resolver->url('img/team-2.jpg');
    expect($missing)->toContain('agcikenegbu.org/site/');
    expect($missing)->not->toContain('AG_IKENEGBU_CHURCH_WEBSITE');

    $stored = $resolver->url('http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/images/rev12.jpg');
    expect($stored)->not->toContain('localhost/AG_IKENEGBU_CHURCH_WEBSITE');
    expect($stored)->toContain('images/rev12.jpg');

    $event = $resolver->url('http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/portal/uploads/events/250d2aa4581313b6313c326d8f54e0a5.jpg');
    expect($event)->not->toContain('localhost/AG_IKENEGBU_CHURCH_WEBSITE');
    expect($event)->toContain('uploads/events/250d2aa4581313b6313c326d8f54e0a5.jpg');
});
