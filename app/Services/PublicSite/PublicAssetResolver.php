<?php

namespace App\Services\PublicSite;

use App\Support\PortalPublicUrl;

/**
 * Resolve public media paths without breaking when a file exists only on the legacy host.
 */
final class PublicAssetResolver
{
    public function url(string $path): string
    {
        $path = $this->normalizeStoredPath($path);
        if ($path === '') {
            return '';
        }

        if ($this->isLegacyLoopbackUrl($path)) {
            $path = $this->normalizeStoredPath($path);
        }

        // Genuine remote URLs stay as-is. Leftover XAMPP hosts must never
        // short-circuit back to the browser after a failed prefix strip.
        if ($this->isRemoteAbsoluteUrl($path)) {
            return $path;
        }

        // Root-absolute media paths from legacy markup → treat as site-relative.
        if (str_starts_with($path, '/') && preg_match('#^/(images|img|uploads|videos|css|js|lib)/#i', $path) === 1) {
            $path = ltrim($path, '/');
        } elseif (str_starts_with($path, '/')) {
            return $path;
        }

        $relative = ltrim($path, '/');

        if (str_starts_with($relative, 'site/')) {
            $relative = substr($relative, 5);
        }

        if (str_starts_with($relative, 'portal/')) {
            return $this->portalTreeUrl(substr($relative, 7));
        }

        if (str_starts_with($relative, 'uploads/')) {
            return $this->uploadUrl($relative);
        }

        $local = public_path('site/'.$relative);
        if (is_file($local)) {
            return asset('site/'.$relative);
        }

        return $this->siteFallback($relative);
    }

    public function browserMediaBase(): string
    {
        if (PortalPublicUrl::prefersSameOriginMedia()) {
            return rtrim(asset('site'), '/');
        }

        return rtrim((string) config('portal.media_base'), '/');
    }

    /** Resolve admin-uploaded media (events, etc.). */
    public function uploadUrl(string $path): string
    {
        $path = $this->normalizeStoredPath($path);
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'site/')) {
            $path = substr($path, 5);
        }

        if (str_starts_with($path, 'portal/')) {
            return $this->portalTreeUrl(substr($path, 7));
        }

        $siteLocal = public_path('site/'.$path);
        if (is_file($siteLocal)) {
            return asset('site/'.$path);
        }

        $storageLocal = storage_path('app/public/'.$path);
        if (is_file($storageLocal)) {
            return asset('storage/'.$path);
        }

        if (PortalPublicUrl::prefersSameOriginMedia()) {
            return asset('site/'.$path);
        }

        $legacyAdminBase = rtrim((string) config('portal.legacy_admin_base'), '/');
        if ($legacyAdminBase !== '') {
            return $this->rejectLegacyBrowserUrl($legacyAdminBase.'/'.$path, 'site/'.$path);
        }

        $mediaBase = rtrim((string) config('portal.media_base'), '/');

        return $this->rejectLegacyBrowserUrl($mediaBase.'/portal/'.$path, 'site/'.$path);
    }

    private function portalTreeUrl(string $relative): string
    {
        $relative = ltrim($relative, '/');
        $portalLocal = public_path('portal/'.$relative);
        if (is_file($portalLocal)) {
            return asset('portal/'.$relative);
        }

        if (str_starts_with($relative, 'uploads/') && is_file(public_path('site/'.$relative))) {
            return asset('site/'.$relative);
        }

        if (PortalPublicUrl::prefersSameOriginMedia()) {
            if (str_starts_with($relative, 'uploads/')) {
                return asset('site/'.$relative);
            }

            return asset('portal/'.$relative);
        }

        $sameOrigin = str_starts_with($relative, 'uploads/')
            ? 'site/'.$relative
            : 'portal/'.$relative;

        $legacyAdminBase = rtrim((string) config('portal.legacy_admin_base'), '/');
        if ($legacyAdminBase !== '') {
            return $this->rejectLegacyBrowserUrl($legacyAdminBase.'/'.$relative, $sameOrigin);
        }

        return $this->rejectLegacyBrowserUrl(
            rtrim((string) config('portal.media_base'), '/').'/portal/'.$relative,
            $sameOrigin
        );
    }

    private function siteFallback(string $relative): string
    {
        if (PortalPublicUrl::prefersSameOriginMedia()) {
            return asset('site/'.$relative);
        }

        return rtrim((string) config('portal.media_base'), '/').'/'.$relative;
    }

    private function normalizeStoredPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        $stripped = preg_replace(
            '#^(?:https?:)?//(?:localhost|127\.0\.0\.1)(?::\d+)?/AG_IKENEGBU_CHURCH_WEBSITE/?#i',
            '',
            $path
        );
        if (is_string($stripped) && $stripped !== $path) {
            $path = ltrim($stripped, '/');
        }

        foreach ($this->localLegacyPrefixes() as $prefix) {
            if ($prefix !== '' && strncasecmp($path, $prefix, strlen($prefix)) === 0) {
                return ltrim(substr($path, strlen($prefix)), '/');
            }
        }

        return $path;
    }

    private function isRemoteAbsoluteUrl(string $path): bool
    {
        if (preg_match('#^(https?:)?//#i', $path) !== 1) {
            return false;
        }

        return ! $this->isLegacyLoopbackUrl($path);
    }

    private function isLegacyLoopbackUrl(string $url): bool
    {
        if (PortalPublicUrl::isLocalLegacyBase($url)) {
            return true;
        }

        return (bool) preg_match(
            '#^(?:https?:)?//(?:localhost|127\.0\.0\.1)(?::\d+)?/AG_IKENEGBU_CHURCH_WEBSITE(?:/|$)#i',
            $url
        );
    }

    private function rejectLegacyBrowserUrl(string $candidate, string $sameOriginRelative): string
    {
        if ($this->isLegacyLoopbackUrl($candidate)) {
            return asset($sameOriginRelative);
        }

        return $candidate;
    }

    /** @return list<string> */
    private function localLegacyPrefixes(): array
    {
        $prefixes = [
            'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/',
            'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/',
            'https://localhost/AG_IKENEGBU_CHURCH_WEBSITE/',
            'https://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/',
        ];

        foreach ([
            config('portal.media_base'),
            config('portal.legacy_public_base'),
            config('portal.legacy_admin_base'),
        ] as $base) {
            $base = rtrim((string) $base, '/');
            if ($base !== '' && PortalPublicUrl::isLocalLegacyBase($base)) {
                $prefixes[] = $base.'/';
            }
        }

        return array_values(array_unique($prefixes));
    }
}
