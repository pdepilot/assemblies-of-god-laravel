<?php

it('serves the public church homepage at root', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(config('identity.public.short_name', 'AGC Ikenegbu'), false);
    $response->assertSee('hero-slider', false);
    $response->assertDontSee('Initializing Platform', false);
});

it('serves admin login under /admin/login', function () {
    $this->get('/admin/login')->assertOk();
});

it('redirects legacy login path to admin login', function () {
    $this->get('/login')->assertRedirect('/admin/login');
});
