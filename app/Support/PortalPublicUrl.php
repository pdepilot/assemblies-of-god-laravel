<?php

namespace App\Support;

/**
 * Distinguishes XAMPP strangler-fig hosts from the Laravel public origin.
 *
 * Local defaults such as http://localhost/AG_IKENEGBU_CHURCH_WEBSITE remain
 * valid for legacy HTTP proxies. Browser-facing HTML must not emit those
 * URLs when the visitor is on the production (or any non-loopback) origin.
 */
final class PortalPublicUrl
{
    public static function isLocalLegacyBase(string $url): bool
    {
        return (bool) preg_match(
            '#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/AG_IKENEGBU_CHURCH_WEBSITE(?:/|$)#i',
            $url
        );
    }

    public static function isLoopbackHost(?string $host): bool
    {
        $host = strtolower(trim((string) $host));

        return $host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1';
    }

    public static function publicOrigin(): string
    {
        return rtrim((string) url('/'), '/');
    }

    /**
     * True when leftover PORTAL_MEDIA_BASE localhost values must not appear in HTML.
     */
    public static function prefersSameOriginMedia(): bool
    {
        $mediaBase = rtrim((string) config('portal.media_base'), '/');
        if ($mediaBase === '' || ! self::isLocalLegacyBase($mediaBase)) {
            return false;
        }

        $originHost = parse_url(self::publicOrigin(), PHP_URL_HOST);

        return ! self::isLoopbackHost(is_string($originHost) ? $originHost : '');
    }
}
