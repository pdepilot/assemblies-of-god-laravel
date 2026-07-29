<?php

namespace App\Services\Testimonies;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SiteTestimonyWriteService
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function updateStatus(int $id, array $input, int $adminId): array
    {
        if (! Schema::hasTable('site_testimonies')) {
            throw new InvalidArgumentException('Testimonies table is not available.');
        }

        $existing = DB::table('site_testimonies')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Testimony not found.');
        }

        $status = strtolower(trim((string) ($input['status'] ?? $existing->status)));
        if (! in_array($status, SiteTestimonyReadService::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid testimony status.');
        }

        $featured = filter_var($input['is_featured'] ?? false, FILTER_VALIDATE_BOOL);
        $featuredValue = $status === 'approved' && $featured ? 1 : 0;

        DB::table('site_testimonies')->where('id', $id)->update([
            'status' => $status,
            'is_featured' => $featuredValue,
            'reviewed_by' => $adminId > 0 ? $adminId : null,
            'reviewed_at' => now(),
        ]);

        $read = new SiteTestimonyReadService;

        return $read->getTestimony($id) ?? (array) $existing;
    }

    public function delete(int $id): void
    {
        if (! Schema::hasTable('site_testimonies')) {
            throw new InvalidArgumentException('Testimonies table is not available.');
        }

        $existing = DB::table('site_testimonies')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Testimony not found.');
        }

        if (! empty($existing->photo_path)) {
            $this->deletePhotoFile((string) $existing->photo_path);
        }

        DB::table('site_testimonies')->where('id', $id)->delete();
    }

    private function deletePhotoFile(string $relativePath): void
    {
        $path = rtrim((string) config('portal.legacy_root'), '/')
            .'/portal/'.ltrim(str_replace('\\', '/', $relativePath), '/');

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
