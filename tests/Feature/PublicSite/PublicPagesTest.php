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

test('public sdgt route is registered and responds', function () {
    $response = $this->get('/sdgt');

    $response->assertOk();
});

test('asset resolver falls back to media base when local file is missing', function () {
    $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);
    $url = $resolver->url('images/definitely-missing-file-xyz.jpg');

    expect($url)->toContain('definitely-missing-file-xyz.jpg');
    expect($url)->toStartWith(rtrim((string) config('portal.media_base'), '/'));
});
