<?php

namespace App\Legacy;

use RuntimeException;

/**
 * Typed bridge to the legacy global ClassicCertificatePdf generator.
 */
final class ClassicCertificatePdfGenerator
{
    private const LEGACY_CLASS = 'ClassicCertificatePdf';

    /**
     * @param array{
     *   church_name: string,
     *   recipient_name: string,
     *   award_title: string,
     *   description: string,
     *   certificate_number: string,
     *   issued_date: string,
     *   verify_url: string,
     *   logo_path?: string,
     *   qr_matrix?: array<string, mixed>|null
     * } $payload
     */
    public static function sundaySchool(array $payload): string
    {
        self::boot();

        /** @var callable(array): string $generator */
        $generator = [self::LEGACY_CLASS, 'sundaySchool'];

        return $generator($payload);
    }

    public static function boot(): void
    {
        if (class_exists(self::LEGACY_CLASS, false)) {
            return;
        }

        $root = rtrim((string) config('portal.legacy_root'), '/');
        if ($root === '' || ! is_dir($root)) {
            throw new RuntimeException('Certificate PDF generator is unavailable. Set PORTAL_LEGACY_ROOT to the legacy site folder.');
        }

        if (! defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', $root);
        }

        if (! defined('ADMIN_ROOT')) {
            define('ADMIN_ROOT', $root.'/portal');
        }

        $pdfClass = ADMIN_ROOT.'/includes/ClassicCertificatePdf.php';
        if (! is_file($pdfClass)) {
            throw new RuntimeException('ClassicCertificatePdf.php was not found in the legacy portal.');
        }

        require_once $pdfClass;
    }
}
