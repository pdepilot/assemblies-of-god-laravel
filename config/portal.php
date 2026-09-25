<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy portal static media (videos, images outside public/portal)
    |--------------------------------------------------------------------------
    | Local default points at the XAMPP public site during strangler-fig
    | migration. Production .env should set these to APP_URL (see .env.example).
    | Browser HTML still rewrites leftover localhost values via
    | App\Support\PortalPublicUrl and PublicAssetResolver.
    */
    'media_base' => rtrim((string) env('PORTAL_MEDIA_BASE', 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE'), '/'),

    'legacy_admin_base' => rtrim((string) env(
        'PORTAL_LEGACY_ADMIN_BASE',
        env('PORTAL_MEDIA_BASE', 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE').'/portal'
    ), '/'),

    'legacy_root' => rtrim((string) env(
        'PORTAL_LEGACY_ROOT',
        'C:/xampp/htdocs/AG_IKENEGBU_CHURCH_WEBSITE'
    ), '/'),

    /*
    |--------------------------------------------------------------------------
    | Public site (sibling pages still on legacy during M11)
    |--------------------------------------------------------------------------
    */
    'legacy_public_base' => rtrim((string) env(
        'PORTAL_LEGACY_PUBLIC_BASE',
        env('PORTAL_MEDIA_BASE', 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE')
    ), '/'),

    'legacy_api_base' => rtrim((string) env(
        'PORTAL_LEGACY_API_BASE',
        env('PORTAL_MEDIA_BASE', 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE').'/api'
    ), '/'),

    'website_pages_path' => (string) env(
        'PORTAL_WEBSITE_PAGES_PATH',
        env('PORTAL_LEGACY_ROOT', 'C:/xampp/htdocs/AG_IKENEGBU_CHURCH_WEBSITE').'/portal/storage/website-pages.json'
    ),

    'brand' => [
        'name' => env('ADMIN_BRAND_NAME', 'AGC IKENEGBU'),
        'subtitle' => env('ADMIN_BRAND_SUBTITLE', 'Church Management System'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin idle session timeout (legacy portal parity)
    |--------------------------------------------------------------------------
    | Lifetime / warning are measured from last user activity, not mere
    | session cookie age. Defaults match legacy: 30 min idle, warn at 25.
    */
    'session_lifetime_seconds' => max(60, (int) env('PORTAL_SESSION_LIFETIME', 1800)),
    'session_warning_seconds' => max(30, (int) env('PORTAL_SESSION_WARNING', 1500)),

    // Brute-force protection (legacy BanManager parity)
    'max_failed_attempts' => max(1, (int) env('PORTAL_MAX_FAILED_ATTEMPTS', 4)),
    'warning_at_attempt' => max(1, (int) env('PORTAL_WARNING_AT_ATTEMPT', 3)),
    'ban_days_level_1' => max(1, (int) env('PORTAL_BAN_DAYS_LEVEL_1', 15)),
    'ban_days_level_2' => max(1, (int) env('PORTAL_BAN_DAYS_LEVEL_2', 90)),

    'notifications' => [
        ['icon' => 'gold', 'title' => 'Migration in progress', 'text' => 'Sidebar includes Laravel modules plus legacy portal links for unfinished screens.', 'time' => 'Now'],
    ],
];
