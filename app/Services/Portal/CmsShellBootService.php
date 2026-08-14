<?php

namespace App\Services\Portal;

use App\Models\Admin;
use App\Services\Auth\RbacNavAccessService;
use App\Support\RbacPlatform;

final class CmsShellBootService
{
    public function __construct(
        private readonly PortalNavService $portalNav,
        private readonly RbacNavAccessService $navAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootData(Admin $admin, string $platform): array
    {
        $platform = RbacPlatform::normalize($platform, RbacPlatform::AG);
        $identity = $this->identityForPlatform($platform);
        $mediaBase = rtrim((string) config('portal.media_base'), '/');
        $logoVideoPath = ltrim((string) $identity['logo_video_path'], '/');
        $faviconPath = ltrim((string) $identity['favicon_path'], '/');

        $homeRoute = $this->portalNav->homeHrefForAdmin($admin, $platform);

        $cmsConfig = $this->portalNav->cmsConfig($admin, $platform);
        $cmsConfig['brand'] = [
            'name' => $identity['brand_name'],
            'subtitle' => $identity['brand_subtitle'],
            'logo_video_url' => $mediaBase.'/'.$logoVideoPath,
            'logo_video_path' => $logoVideoPath,
            'favicon_url' => $mediaBase.'/'.$faviconPath,
            'search_placeholder' => $identity['search_placeholder'],
            'platform' => $platform,
        ];

        $navAccess = $this->navAccess->getNavAccess($admin, $platform);
        $dashboardAccess = $this->navAccess->getDashboardAccess(
            $admin,
            $homeRoute,
            $identity['brand_subtitle'],
            $platform,
        );

        return [
            'platform' => $platform,
            'identity' => $identity,
            'favicon_url' => $mediaBase.'/'.$faviconPath,
            'config' => $cmsConfig,
            'media_base' => config('portal.media_base'),
            'admin_base' => url('/admin'),
            'legacy_admin_base' => config('portal.legacy_admin_base'),
            'brand_subtitle' => $identity['brand_subtitle'],
            'admin_user' => [
                'name' => $admin->display_name,
                'role' => ucfirst(str_replace('_', ' ', (string) $admin->role)),
                'email' => $admin->email,
            ],
            'logout_url' => route('logout'),
            'settings_url' => route('settings.index'),
            'user_ui_pref' => [
                'theme' => $admin->ui_theme ?? 'gold',
                'mode' => $admin->ui_mode ?? 'dark',
            ],
            'home_route' => $homeRoute,
            'nav_access' => $navAccess,
            'dashboard_access' => $dashboardAccess,
            'session' => [
                'apiUrl' => route('admin.session'),
                'loginUrl' => route('login', ['reason' => 'inactivity']),
                'lifetimeMs' => max(60, (int) config('portal.session_lifetime_seconds', 1800)) * 1000,
                'warningMs' => max(30, (int) config('portal.session_warning_seconds', 1500)) * 1000,
            ],
        ];
    }

    /**
     * @return array{
     *     brand_name: string,
     *     brand_subtitle: string,
     *     page_title_suffix: string,
     *     favicon_path: string,
     *     logo_video_path: string,
     *     search_placeholder: string
     * }
     */
    public function identityForPlatform(string $platform): array
    {
        $admin = config('identity.admin', []);

        return [
            'brand_name' => (string) ($admin['brand_name'] ?? 'AGC IKENEGBU'),
            'brand_subtitle' => (string) ($admin['brand_subtitle'] ?? 'Church Management System'),
            'page_title_suffix' => (string) ($admin['page_title_suffix'] ?? 'AGC IKENEGBU CMS'),
            'favicon_path' => (string) ($admin['favicon_path'] ?? 'images/ag-logo.jpeg'),
            'logo_video_path' => (string) ($admin['primary_logo_video_path'] ?? 'videos/Create_a_cinematic_D_animatio.mp4'),
            'search_placeholder' => (string) ($admin['search_placeholder'] ?? 'Search members, events, pages...'),
        ];
    }
}
