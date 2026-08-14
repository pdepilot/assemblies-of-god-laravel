<?php

use App\Services\CommunicationHub\ChurchEmailBrandingService;

test('wrapHtml applies church header footer and logo cid', function () {
    $branding = app(ChurchEmailBrandingService::class);

    $html = $branding->wrapHtml('<p>Hello member</p>', [
        'from_name' => 'AGC Ikenegbu',
        'church_website' => 'https://www.agikenebgu.org',
        'church_address' => 'Owerri, Imo State',
        'church_phone' => '08012345678',
        'church_email' => 'info@agikenebgu.org',
    ], 'Preview text');

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('cid:'.ChurchEmailBrandingService::LOGO_CID)
        ->toContain('AGC Ikenegbu')
        ->toContain('Owerri, Imo State')
        ->toContain('info@agikenebgu.org')
        ->toContain('<p>Hello member</p>')
        ->toContain('All rights reserved.')
        ->toContain('Preview text');
});

test('isAlreadyBranded detects wrapped html', function () {
    $branding = app(ChurchEmailBrandingService::class);

    $wrapped = $branding->wrapHtml('<p>Body</p>', ['from_name' => 'AGC Ikenegbu']);

    expect($branding->isAlreadyBranded($wrapped))->toBeTrue()
        ->and($branding->isAlreadyBranded('<p>Plain body</p>'))->toBeFalse();
});
