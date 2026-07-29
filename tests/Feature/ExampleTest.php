<?php

it('serves the public church homepage at root', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('AG', false);
    $response->assertSee('Ikenebgu', false);
});
