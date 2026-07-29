<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolAward;
use App\Models\SundaySchoolCertificate;
use InvalidArgumentException;
use RuntimeException;

final class CertificateWriteService
{
    public function __construct(
        private readonly CertificateReadService $read,
        private readonly CertificatePdfService $pdf,
    ) {}

    /** @return array<string, mixed> */
    public function generateFromAward(int $awardId, int $adminId): array
    {
        $award = SundaySchoolAward::query()->find($awardId);
        if (! $award) {
            throw new InvalidArgumentException('Award not found.');
        }

        if (! in_array((string) $award->status, ['approved', 'published'], true)) {
            throw new InvalidArgumentException('Award must be approved before certificate generation.');
        }

        $existing = $this->read->findByAwardId($awardId);
        if ($existing !== null) {
            return $this->regenerateCertificate((int) $existing['id'], $adminId);
        }

        return $this->issueCertificate(
            (string) $award->recipient_type,
            (string) $award->recipient_name,
            (string) $award->award_name,
            (string) ($award->achievement_summary ?: 'Recognized for excellence in Sunday School.'),
            $awardId,
            $adminId,
        );
    }

    /** @return array<string, mixed> */
    public function regenerateCertificate(int $certificateId, int $adminId): array
    {
        $existing = SundaySchoolCertificate::query()->find($certificateId);
        if (! $existing) {
            throw new InvalidArgumentException('Certificate not found.');
        }

        $description = trim((string) ($existing->achievement_description ?? ''));
        if ($description === '') {
            $description = 'Recognized for excellence in Sunday School.';
        }

        $recipientType = (string) $existing->recipient_type;
        $recipientName = (string) $existing->recipient_name;
        $awardTitle = (string) $existing->award_title;

        if ($existing->award_id) {
            $award = SundaySchoolAward::query()->find((int) $existing->award_id);
            if ($award) {
                $recipientType = (string) $award->recipient_type;
                $recipientName = (string) $award->recipient_name;
                $awardTitle = (string) $award->award_name;
                $description = trim((string) ($award->achievement_summary ?? $description)) ?: $description;
            }
        }

        return $this->replaceCertificatePdf($existing, $recipientType, $recipientName, $awardTitle, $description, $adminId);
    }

    public function deleteCertificate(int $certificateId): void
    {
        $existing = SundaySchoolCertificate::query()->find($certificateId);
        if (! $existing) {
            throw new InvalidArgumentException('Certificate not found.');
        }

        $path = $this->read->resolveFilePath((string) $existing->file_path);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }

        $existing->delete();
    }

    /** @return array<string, mixed> */
    private function issueCertificate(
        string $recipientType,
        string $recipientName,
        string $awardTitle,
        string $description,
        ?int $awardId,
        int $adminId,
    ): array {
        $certNumber = 'SS-CERT-'.date('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $verifyCode = bin2hex(random_bytes(16));
        $relativePath = $this->writePdfFile($certNumber, $verifyCode, $recipientName, $awardTitle, $description, date('Y-m-d'));

        $certificate = SundaySchoolCertificate::query()->create([
            'certificate_number' => $certNumber,
            'verification_code' => $verifyCode,
            'award_id' => $awardId,
            'recipient_type' => $recipientType,
            'recipient_name' => $recipientName,
            'award_title' => $awardTitle,
            'achievement_description' => $description,
            'issued_date' => date('Y-m-d'),
            'file_path' => $relativePath,
            'issued_by' => $adminId,
            'status' => 'issued',
        ]);

        return $this->read->getCertificate((int) $certificate->id) ?? [];
    }

    /** @return array<string, mixed> */
    private function replaceCertificatePdf(
        SundaySchoolCertificate $existing,
        string $recipientType,
        string $recipientName,
        string $awardTitle,
        string $description,
        int $adminId,
    ): array {
        $certNumber = (string) $existing->certificate_number;
        $verifyCode = (string) $existing->verification_code;
        $oldPath = $this->read->resolveFilePath((string) $existing->file_path);

        $relativePath = $this->writePdfFile(
            $certNumber,
            $verifyCode,
            $recipientName,
            $awardTitle,
            $description,
            (string) $existing->issued_date,
        );

        if ($oldPath !== null && $oldPath !== $this->read->resolveFilePath($relativePath) && is_file($oldPath)) {
            @unlink($oldPath);
        }

        $existing->update([
            'recipient_type' => $recipientType,
            'recipient_name' => $recipientName,
            'award_title' => $awardTitle,
            'achievement_description' => $description,
            'file_path' => $relativePath,
            'issued_by' => $adminId,
            'status' => 'issued',
        ]);

        return $this->read->getCertificate((int) $existing->id) ?? [];
    }

    private function writePdfFile(
        string $certNumber,
        string $verifyCode,
        string $recipientName,
        string $awardTitle,
        string $description,
        string $issuedDate,
    ): string {
        $issuedLabel = date('jS \of F Y', strtotime($issuedDate) ?: time());
        $pdfBytes = $this->pdf->sundaySchool([
            'recipient_name' => $recipientName,
            'award_title' => $awardTitle,
            'description' => $description,
            'certificate_number' => $certNumber,
            'issued_date' => $issuedLabel,
            'verify_url' => $this->buildVerifyUrl($verifyCode),
        ]);

        $relativePath = 'uploads/sunday-school/certificates/'.$certNumber.'.pdf';
        $directory = public_path('site/uploads/sunday-school/certificates');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create certificate upload directory.');
        }

        $fullPath = $directory.DIRECTORY_SEPARATOR.$certNumber.'.pdf';
        if (file_put_contents($fullPath, $pdfBytes) === false) {
            throw new RuntimeException('Unable to save certificate PDF.');
        }

        return $relativePath;
    }

    private function buildVerifyUrl(string $verifyCode): string
    {
        $base = rtrim((string) config('portal.legacy_public_base'), '/');
        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        }

        return $base.'/api/verify-sunday-school-certificate?code='.rawurlencode(trim($verifyCode));
    }
}
