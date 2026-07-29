<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class TeamSectionWriteService
{
    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveMember(array $data, ?UploadedFile $photo = null, bool $removePhoto = false): array
    {
        $id = (int) ($data['id'] ?? 0);
        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            throw new InvalidArgumentException('Team member name is required.');
        }

        $existing = $id > 0
            ? DB::table('site_team_members')->where('id', $id)->first()
            : null;
        $photoPath = $existing?->photo_path ? (string) $existing->photo_path : null;

        if ($photo !== null) {
            $uploaded = $this->storePhoto($photo);
            if ($photoPath) {
                $this->deletePhoto($photoPath);
            }
            $photoPath = $uploaded;
        } elseif ($removePhoto) {
            if ($photoPath) {
                $this->deletePhoto($photoPath);
            }
            $photoPath = null;
        }

        $payload = [
            'site' => (string) ($data['site'] ?? 'ag'),
            'member_type' => (string) ($data['member_type'] ?? 'member'),
            'full_name' => $fullName,
            'role_title' => trim((string) ($data['role_title'] ?? '')),
            'bio' => (string) ($data['bio'] ?? ''),
            'photo_path' => $photoPath,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => ! empty($data['is_active']) && (string) $data['is_active'] !== '0',
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('site_team_members')->where('id', $id)->update($payload);
        } else {
            DB::table('site_team_members')->insert($payload + [
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('site_team_members')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    private function storePhoto(UploadedFile $image): string
    {
        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Photo must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Photo must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_IMAGE_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/website/team/'.$filename;
        $directory = public_path('site/uploads/website/team');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create team photo upload directory.');
        }

        if (! $image->move($directory, $filename)) {
            throw new RuntimeException('Unable to save team photo.');
        }

        return $relativePath;
    }

    private function deletePhoto(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/website/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }
}
