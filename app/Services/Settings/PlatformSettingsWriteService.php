<?php

namespace App\Services\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class PlatformSettingsWriteService
{
    private const MAX_LOGO_BYTES = 5242880;

    private const ALLOWED_LOGO_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly PlatformSettingsReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveGroup(
        string $groupKey,
        array $payload,
        int $adminId,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): array {
        if (! Schema::hasTable('platform_setting_groups')) {
            throw new InvalidArgumentException('Platform settings storage is unavailable.');
        }

        if (! in_array($groupKey, ['general', 'church', 'website_design', 'rbac'], true)) {
            throw new InvalidArgumentException('Unknown settings group.');
        }

        if ($groupKey === 'website_design') {
            $payload = $this->sanitizeColors($payload);
        }

        if ($groupKey === 'rbac') {
            $payload = [
                'enforcement_enabled' => ! empty($payload['enforcement_enabled']),
                'debug_enabled' => ! empty($payload['debug_enabled']),
            ];
        }

        if ($groupKey === 'church') {
            unset($payload['logo_path'], $payload['logo'], $payload['remove_logo']);
            $current = $this->read->getGroup('church');
            $logoPath = trim((string) ($current['logo_path'] ?? ''));

            if ($logo instanceof UploadedFile) {
                $uploaded = $this->storeLogo($logo);
                if ($logoPath !== '') {
                    $this->deleteLogo($logoPath);
                }
                $payload['logo_path'] = $uploaded;
            } elseif ($removeLogo) {
                if ($logoPath !== '') {
                    $this->deleteLogo($logoPath);
                }
                $payload['logo_path'] = '';
            }
        }

        $merged = array_replace_recursive($this->read->getGroup($groupKey), $payload);
        $json = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new InvalidArgumentException('Unable to encode settings.');
        }

        DB::table('platform_setting_groups')->updateOrInsert(
            ['group_key' => $groupKey],
            [
                'settings' => $json,
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]
        );

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAdminPreferences(int $adminId, array $data): void
    {
        $theme = in_array(($data['ui_theme'] ?? ''), ['gold', 'blue', 'emerald', 'rose'], true)
            ? (string) $data['ui_theme']
            : 'gold';
        $mode = in_array(($data['ui_mode'] ?? ''), ['dark', 'light'], true)
            ? (string) $data['ui_mode']
            : 'dark';

        DB::table('admins')->where('id', $adminId)->update([
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'ui_theme' => $theme,
            'ui_mode' => $mode,
            'updated_at' => now(),
        ]);
    }

    private function storeLogo(UploadedFile $image): string
    {
        if (! isset(self::ALLOWED_LOGO_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Logo must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_LOGO_BYTES) {
            throw new InvalidArgumentException('Logo must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_LOGO_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/settings/church/'.$filename;
        $directory = public_path('site/uploads/settings/church');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create church logo upload directory.');
        }

        if (! $image->move($directory, $filename)) {
            throw new RuntimeException('Unable to save church logo.');
        }

        return $relativePath;
    }

    private function deleteLogo(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/settings/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeColors(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_color')) {
                continue;
            }
            $color = trim((string) $value);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
                unset($payload[$key]);
            } else {
                $payload[$key] = strtoupper($color);
            }
        }

        return $payload;
    }
}
