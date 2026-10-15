<?php

test('public layout renders cookie banner markup and assets', function () {
    $response = $this->get(route('public.home'));

    $response->assertOk();
    $response->assertSee('id="agCookieBanner"', false);
    $response->assertSee('id="agCookieModal"', false);
    $response->assertSee('id="agCookieBreak"', false);
    $response->assertSee('data-action="accept-all"', false);
    $response->assertSee('data-action="essential-only"', false);
    $response->assertSee('data-action="customize"', false);
    $response->assertSee('data-action="save-prefs"', false);
    $response->assertSee('site/css/ag-cookie-banner.css', false);
    $response->assertSee('site/js/ag-cookie-banner.js', false);
    $response->assertSee('Your Privacy, Our Covenant', false);
});

test('cookie banner stylesheet keeps mobile banner visible without display none', function () {
    $css = (string) file_get_contents(public_path('site/css/ag-cookie-banner.css'));

    expect($css)->toContain('@media (max-width: 768px)')
        ->and($css)->toContain('.ag-cookie-banner.is-breaking')
        ->and($css)->toContain('safe-area-inset-bottom')
        ->and($css)->not->toMatch('/@media[^{]+\{[^}]*\.ag-cookie-banner[^}]*display\s*:\s*none/i');
});

test('cookie banner script settles visibility when animationend is missing', function () {
    $js = (string) file_get_contents(public_path('site/js/ag-cookie-banner.js'));

    expect($js)->toContain('function revealBanner')
        ->and($js)->toContain('setTimeout(settle, 1200)')
        ->and($js)->toContain("e.target !== banner")
        ->and($js)->toContain("ag_ikenebgu_cookie_consent");
});
