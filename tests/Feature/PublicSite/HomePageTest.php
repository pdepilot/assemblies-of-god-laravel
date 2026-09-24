<?php

it('serves the public church homepage at root', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(config('identity.public.short_name', 'AGC Ikenegbu'), false);
    $response->assertSee('hero-slider', false);
    $response->assertSee('Transforming the lives of people to be heavenly conscious and earthly useful', false);
    $response->assertSee('Testimonies', false);
    $response->assertSee('Our Worship', false);
    $response->assertSee('Sunday Worship Services', false);
    $response->assertSee('Real grace, healing, and transformation in Christ.', false);
    $response->assertDontSee('Initializing Platform', false);
});

it('serves administrator login under /portal/login', function () {
    /** @var \Tests\TestCase $this */
    $this->get('/portal/login')->assertOk();
});

it('redirects legacy login paths to portal login', function () {
    /** @var \Tests\TestCase $this */
    $this->get('/login')->assertRedirect('/portal/login');
    $this->get('/admin/login')->assertRedirect('/portal/login');
    $this->get('/portal/login.php')->assertRedirect('/portal/login');
});
