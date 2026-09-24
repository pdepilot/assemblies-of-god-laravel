<?php

test('public site links visitors to send down thy glory', function () {
    /** @var \Tests\TestCase $this */
    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('btn-sdtg-nav', false);
    $home->assertSee('Send Down Thy Glory');
    $home->assertSee('https://senddownthyglory.org', false);

    $about = $this->get(route('public.about'));
    $about->assertOk();
    $about->assertSee('btn-sdtg-nav', false);
    $about->assertSee('Send Down Thy Glory');
    $about->assertSee('https://senddownthyglory.org', false);
});
