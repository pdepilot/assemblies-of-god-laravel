<?php

namespace App\Services\PublicSite;

/**
 * Resolve public media paths without breaking when a file exists only on the legacy host.
 */
final class PublicAssetResolver
{
    public function url(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $path) === 1) {
            return $path;
        }

        // Root-absolute media paths from legacy markup → treat as site-relative.
        if (str_starts_with($path, '/') && preg_match('#^/(images|img|uploads|videos|css|js|lib)/#i', $path) === 1) {
            $path = ltrim($path, '/');
        } elseif (str_starts_with($path, '/')) {
            return $path;
        }

        $relative = ltrim($path, '/');

        // Strip accidental site/ prefix once for local lookup variants.
        if (str_starts_with($relative, 'site/')) {
            $relative = substr($relative, 5);
        }

        if (str_starts_with($relative, 'uploads/')) {
            return $this->uploadUrl($relative);
        }

        $local = public_path('site/'.$relative);
        if (is_file($local)) {
            return asset('site/'.$relative);
        }

        $mediaBase = rtrim((string) config('portal.media_base'), '/');

        return $mediaBase.'/'.$relative;
    }

    /** Resolve admin-uploaded media (events, etc.). */
    public function uploadUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'site/')) {
            $path = substr($path, 5);
        }

        $siteLocal = public_path('site/'.$path);
        if (is_file($siteLocal)) {
            return asset('site/'.$path);
        }

        $storageLocal = storage_path('app/public/'.$path);
        if (is_file($storageLocal)) {
            return asset('storage/'.$path);
        }

        $legacyAdminBase = rtrim((string) config('portal.legacy_admin_base'), '/');
        if ($legacyAdminBase !== '') {
            return $legacyAdminBase.'/'.$path;
        }

        $mediaBase = rtrim((string) config('portal.media_base'), '/');

        return $mediaBase.'/portal/'.$path;
    }
}
