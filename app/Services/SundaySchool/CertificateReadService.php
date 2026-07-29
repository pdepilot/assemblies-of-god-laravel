<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class CertificateReadService
{
    /** @return list<array<string, mixed>> */
    public function listCertificates(int $limit = 50): array
    {
        return DB::table('sunday_school_certificates')
            ->orderByDesc('created_at')
            ->limit(max(1, min(200, $limit)))
            ->get()
            ->map(fn ($row) => $this->formatCertificate((array) $row))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getCertificate(int $id): ?array
    {
        $row = DB::table('sunday_school_certificates')->where('id', $id)->first();

        return $row ? $this->formatCertificate((array) $row) : null;
    }

    /** @return array<string, mixed>|null */
    public function findByAwardId(int $awardId): ?array
    {
        if ($awardId <= 0) {
            return null;
        }

        $row = DB::table('sunday_school_certificates')
            ->where('award_id', $awardId)
            ->orderByDesc('created_at')
            ->first();

        return $row ? $this->formatCertificate((array) $row) : null;
    }

    /** @return array{path: string, number: string}|null */
    public function getCertificateFile(int $id): ?array
    {
        $row = DB::table('sunday_school_certificates')
            ->where('id', $id)
            ->first(['file_path', 'certificate_number']);

        if (! $row) {
            return null;
        }

        $path = $this->resolveFilePath((string) $row->file_path);
        if ($path === null) {
            return null;
        }

        return [
            'path' => $path,
            'number' => (string) $row->certificate_number,
        ];
    }

    public function resolveFilePath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ($relativePath === '') {
            return null;
        }

        $candidates = [
            public_path('site/'.$relativePath),
            storage_path('app/public/'.$relativePath),
        ];

        $legacyAdmin = rtrim((string) config('portal.legacy_admin_base'), '/');
        if ($legacyAdmin !== '') {
            $candidates[] = $legacyAdmin.'/'.$relativePath;
        }

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatCertificate(array $row): array
    {
        $id = (int) $row['id'];

        return [
            'id' => $id,
            'certificate_number' => (string) $row['certificate_number'],
            'verification_code' => (string) $row['verification_code'],
            'award_id' => isset($row['award_id']) ? (int) $row['award_id'] : null,
            'recipient_type' => (string) $row['recipient_type'],
            'recipient_name' => (string) $row['recipient_name'],
            'award_title' => (string) $row['award_title'],
            'achievement_description' => (string) ($row['achievement_description'] ?? ''),
            'issued_date' => (string) $row['issued_date'],
            'file_path' => (string) $row['file_path'],
            'issued_by' => isset($row['issued_by']) ? (int) $row['issued_by'] : null,
            'status' => (string) ($row['status'] ?? 'issued'),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'preview_url' => route('ss.certificates.preview', $id),
            'download_url' => route('ss.certificates.download', $id),
            'has_file' => $this->resolveFilePath((string) $row['file_path']) !== null,
        ];
    }
}
