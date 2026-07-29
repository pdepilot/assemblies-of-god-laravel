<?php

namespace App\Services\Analytics;

final class TrafficSupport
{
    private const MAX_PATH = 500;

    public static function normalizeUuid(string $value): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value)) {
            return $value;
        }

        return '';
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            $path = '/';
        }

        $path = preg_replace('/[?#].*$/', '', $path) ?? $path;

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return mb_substr($path, 0, self::MAX_PATH);
    }

    public static function isInternalPath(string $path): bool
    {
        $p = strtolower($path);

        return (bool) preg_match('#/(portal|erp)(/|$)#', $p);
    }

    public static function resolveSiteArea(string $path, string $hint): string
    {
        $hint = strtolower(trim($hint));

        if (in_array($hint, ['ag', 'sdgt', 'sermon', 'register'], true)) {
            return $hint;
        }

        $p = strtolower($path);

        if (str_contains($p, '/sdgt')) {
            return 'sdgt';
        }

        if (str_contains($p, '/sermon-library')) {
            return 'sermon';
        }

        if (str_contains($p, '/register')) {
            return 'register';
        }

        return 'ag';
    }

    /**
     * @param  array<string, mixed>  $hints
     * @return array{device_type: string, browser: string, os: string}
     */
    public static function parseUserAgent(string $ua, array $hints = []): array
    {
        $deviceHint = strtolower(trim((string) ($hints['type'] ?? '')));
        $uaLower = strtolower($ua);

        if (in_array($deviceHint, ['mobile', 'tablet', 'desktop'], true)) {
            $device = $deviceHint;
        } elseif (preg_match('/ipad|tablet|kindle|playbook|silk/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobi|iphone|android.*mobile|windows phone/i', $ua)) {
            $device = 'mobile';
        } else {
            $device = 'desktop';
        }

        $browser = 'Unknown';

        if (str_contains($uaLower, 'edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($uaLower, 'chrome/') && ! str_contains($uaLower, 'edg/')) {
            $browser = 'Chrome';
        } elseif (str_contains($uaLower, 'firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($uaLower, 'safari/') && ! str_contains($uaLower, 'chrome/')) {
            $browser = 'Safari';
        } elseif (str_contains($uaLower, 'opera') || str_contains($uaLower, 'opr/')) {
            $browser = 'Opera';
        }

        $os = 'Unknown';

        if (str_contains($uaLower, 'windows')) {
            $os = 'Windows';
        } elseif (str_contains($uaLower, 'android')) {
            $os = 'Android';
        } elseif (str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipad') || str_contains($uaLower, 'ios')) {
            $os = 'iOS';
        } elseif (str_contains($uaLower, 'mac os') || str_contains($uaLower, 'macintosh')) {
            $os = 'macOS';
        } elseif (str_contains($uaLower, 'linux')) {
            $os = 'Linux';
        }

        return [
            'device_type' => $device,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    public static function hashIp(string $ip): string
    {
        $ip = trim($ip);

        if ($ip === '') {
            return '';
        }

        $salt = (string) config('traffic.ip_salt', 'ag-ikenebgu-site-traffic-v1');

        return hash('sha256', $salt.'|'.$ip);
    }

    public static function isPrivateIp(string $ip): bool
    {
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    public static function newUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
