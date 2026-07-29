<?php

use App\Legacy\ClassicCertificatePdfGenerator;
use App\Services\SundaySchool\CertificatePdfService;

test('certificate pdf service throws when legacy generator is unavailable', function () {
    config(['portal.legacy_root' => '']);

    expect(fn () => app(CertificatePdfService::class)->sundaySchool([
        'recipient_name' => 'Test Student',
        'award_title' => 'Best Attendance',
        'description' => 'Excellent attendance.',
        'certificate_number' => 'SS-CERT-TEST-001',
        'issued_date' => '2026-06-22',
        'verify_url' => 'https://example.com/verify/abc',
    ]))->toThrow(\RuntimeException::class, 'Certificate PDF generator is unavailable');
});

test('legacy certificate generator boots from configured legacy root', function () {
    $legacyRoot = rtrim((string) config('portal.legacy_root'), '/');
    if ($legacyRoot === '' || ! is_file($legacyRoot.'/portal/includes/ClassicCertificatePdf.php')) {
        $this->markTestSkipped('Legacy ClassicCertificatePdf.php is not configured for this environment.');
    }

    ClassicCertificatePdfGenerator::boot();

    expect(class_exists('ClassicCertificatePdf', false))->toBeTrue();
});
