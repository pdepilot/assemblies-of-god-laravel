<?php

namespace App\Services\SundaySchool;

use App\Legacy\ClassicCertificatePdfGenerator;

/**
 * Bridge to the legacy ClassicCertificatePdf generator during strangler-fig migration.
 */
final class CertificatePdfService
{
    /** @param array<string, mixed> $data */
    public function sundaySchool(array $data): string
    {
        return ClassicCertificatePdfGenerator::sundaySchool([
            'church_name' => (string) ($data['church_name'] ?? 'Assemblies of God Church Ikenebgu'),
            'recipient_name' => (string) $data['recipient_name'],
            'award_title' => (string) $data['award_title'],
            'description' => (string) $data['description'],
            'certificate_number' => (string) $data['certificate_number'],
            'issued_date' => (string) $data['issued_date'],
            'verify_url' => (string) $data['verify_url'],
            'logo_path' => (string) ($data['logo_path'] ?? $this->resolveLogoPath()),
        ]);
    }

    public function resolveLogoPath(): string
    {
        $configured = app(\App\Services\Settings\PlatformSettingsReadService::class)->churchLogoAbsolutePath();
        if ($configured !== null) {
            return $configured;
        }

        foreach ([
            public_path('site/images/ag-logo.jpeg'),
            public_path('images/ag-logo.jpeg'),
            rtrim((string) config('portal.legacy_root'), '/').'/images/ag-logo.jpeg',
        ] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return public_path('site/images/ag-logo.jpeg');
    }
}
