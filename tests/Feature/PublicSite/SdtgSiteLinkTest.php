<?php

test('public navbar links to the separate send down thy glory website', function () {
    $url = rtrim((string) config('identity.public.sdtg_site_url'), '/');
    $label = (string) config('identity.public.sdtg_site_label');

    expect($url)->toBe('https://senddownthyglory.org');
    expect($label)->toBe('Send Down Thy Glory');

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('btn-sdtg-nav', false)
        ->assertSee($url, false)
        ->assertSee($label)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false);

    $this->get(route('public.about'))
        ->assertOk()
        ->assertSee('btn-sdtg-nav', false)
        ->assertSee($url, false);
});
