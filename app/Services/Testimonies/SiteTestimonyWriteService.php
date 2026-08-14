<?php

namespace App\Services\Testimonies;

use App\Services\Security\SecurityAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SiteTestimonyWriteService
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    /**
     * Public website testimony submission.
     *
     * @param  array<string, mixed>  $input
     * @return array{id: int, status: string}
     */
    public function submit(array $input, ?string $ip = null): array
    {
        if (! Schema::hasTable('site_testimonies')) {
            throw new InvalidArgumentException('Testimonies are temporarily unavailable.');
        }

        $name = trim((string) ($input['name'] ?? $input['full_name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $role = trim((string) ($input['role'] ?? $input['role_title'] ?? ''));
        $text = trim((string) ($input['testimony'] ?? $input['testimony_text'] ?? ''));
        $sourcePage = Str::slug(trim((string) ($input['source_page'] ?? 'index'))) ?: 'index';
        if (mb_strlen($sourcePage) > 60) {
            $sourcePage = mb_substr($sourcePage, 0, 60);
        }

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Please enter your full name.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if ($text === '' || mb_strlen($text) < 20) {
            throw new InvalidArgumentException('Please share a testimony of at least 20 characters.');
        }

        $photoPath = $this->storeOptionalPhoto((string) ($input['photo'] ?? ''));

        $id = (int) DB::table('site_testimonies')->insertGetId([
            'full_name' => mb_substr($name, 0, 150),
            'email' => mb_substr($email, 0, 255),
            'role_title' => $role !== '' ? mb_substr($role, 0, 120) : null,
            'testimony_text' => $text,
            'photo_path' => $photoPath,
            'source_page' => $sourcePage,
            'status' => 'pending',
            'is_featured' => false,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);

        $this->audit->log(
            'site_testimony_submitted',
            'New testimony from '.$name.' (pending review).',
            null,
            'info',
            ['testimony_id' => $id, 'source_page' => $sourcePage],
        );

        return ['id' => $id, 'status' => 'pending'];
    }

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

    private function storeOptionalPhoto(string $photoData): ?string
    {
        $photoData = trim($photoData);
        if ($photoData === '' || ! preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $photoData, $m)) {
            return null;
        }

        $binary = base64_decode(substr($photoData, strpos($photoData, ',') + 1), true);
        if ($binary === false || strlen($binary) < 32 || strlen($binary) > 5_242_880) {
            return null;
        }

        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $relative = 'uploads/testimonies/'.Str::random(32).'.'.$ext;
        $absoluteDir = public_path('site/uploads/testimonies');
        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            return null;
        }

        if (@file_put_contents(public_path('site/'.$relative), $binary) === false) {
            return null;
        }

        return $relative;
    }

    private function deletePhotoFile(string $relativePath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return;
        }

        $sitePath = public_path('site/'.$relativePath);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }

        $legacyPath = rtrim((string) config('portal.legacy_root'), '/')
            .'/portal/'.$relativePath;
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
