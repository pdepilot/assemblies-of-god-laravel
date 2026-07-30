@php
    $isSdtgContext = request()->is('admin/sdtg') || request()->is('admin/sdtg/*');
    $admin = $isSdtgContext
        ? (auth('sdtg')->user() ?? auth('admin')->user())
        : (auth('admin')->user() ?? auth('sdtg')->user());
    $portalNav = app(\App\Services\Portal\PortalNavService::class);
    $navAccess = app(\App\Services\Auth\RbacNavAccessService::class);
    $cmsConfig = $portalNav->cmsConfig($admin);
    $activePage = $portalPage ?? $portalNav->resolveActivePage();
    $homeRoute = $isSdtgContext ? route('sdtg.dashboard') : $portalNav->homeHrefForAdmin($admin);
    $cmsNavAccess = $navAccess->getNavAccess($admin);
    $cmsDashboardAccess = $navAccess->getDashboardAccess($admin, $homeRoute);
    $isSundaySchool = $activePage === 'sunday-school' || str_starts_with(request()->path(), 'admin/sunday-school');
    $isSsLegacyShell = $isSundaySchool && request()->routeIs('ss.analytics.index');
    $isSettingsShell = request()->routeIs('settings.*');
    $isCmsNativeShell = $isSsLegacyShell || $isSettingsShell;
    $portalCss = asset('portal/css');
    $portalJs = asset('portal/js');
    $adminIdentity = config('identity.admin');
    $adminFavicon = rtrim((string) config('portal.media_base'), '/').'/'.ltrim((string) ($adminIdentity['favicon_path'] ?? 'images/ag-logo.jpeg'), '/');
    $logoutUrl = $isSdtgContext ? route('sdtg.logout') : route('logout');
    $sessionApiUrl = $isSdtgContext ? route('sdtg.session') : route('admin.session');
    $loginUrl = $isSdtgContext
        ? route('sdtg.login', ['reason' => 'inactivity'])
        : route('login', ['reason' => 'inactivity']);
    $cmsAdminUser = [
        'name' => $admin->display_name,
        'role' => ucfirst(str_replace('_', ' ', (string) $admin->role)),
        'email' => $admin->email,
    ];
    $cmsUserUiPref = [
        'theme' => $admin->ui_theme ?? 'gold',
        'mode' => $admin->ui_mode ?? 'dark',
    ];
    $cmsBootData = [
        'config' => $cmsConfig,
        'media_base' => config('portal.media_base'),
        'admin_base' => url('/admin'),
        'legacy_admin_base' => config('portal.legacy_admin_base'),
        'brand_subtitle' => $isSdtgContext
            ? ($adminIdentity['login_secondary_subtitle'] ?? 'Event Management Platform')
            : ($adminIdentity['brand_subtitle'] ?? 'Church Management System'),
        'admin_user' => $cmsAdminUser,
        'logout_url' => $logoutUrl,
        'settings_url' => route('settings.index'),
        'user_ui_pref' => $cmsUserUiPref,
        'home_route' => $homeRoute,
        'nav_access' => $cmsNavAccess,
        'dashboard_access' => $cmsDashboardAccess,
        'session' => [
            'apiUrl' => $sessionApiUrl,
            'loginUrl' => $loginUrl,
            'lifetimeMs' => max(60, (int) config('portal.session_lifetime_seconds', 1800)) * 1000,
            'warningMs' => max(30, (int) config('portal.session_warning_seconds', 1500)) * 1000,
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#070b16">
    <title>{{ $title ?? 'Dashboard' }} | {{ $adminIdentity['page_title_suffix'] ?? 'AGC IKENEGBU CMS' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ $portalCss }}/main.css">
    <link rel="stylesheet" href="{{ $portalCss }}/laravel-overrides.css">
    @if ($isCmsNativeShell)
        @vite(['resources/js/app.js'])
        @if ($isSsLegacyShell)
            <link rel="stylesheet" href="{{ $portalCss }}/sunday-school.css">
        @endif
    @elseif ($isSundaySchool)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ $portalCss }}/sunday-school.css">
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="icon" href="{{ $adminFavicon }}" type="image/jpeg">
</head>
<body class="cms-app{{ $isSundaySchool ? ' cms-app--sunday-school' : '' }}" data-page="{{ $isSettingsShell ? 'settings' : ($isSundaySchool ? 'sunday-school' : $activePage) }}" data-depth="0" data-theme="{{ $admin->ui_theme ?? 'gold' }}" data-mode="{{ $admin->ui_mode ?? 'dark' }}">

    <form id="cmsLogoutForm" method="POST" action="{{ $logoutUrl }}" hidden>
        @csrf
    </form>

    <div class="cms-loader" id="cmsLoader" aria-hidden="true">
        <div class="cms-loader__ring"></div>
        <p class="cms-loader__text">{{ $isSundaySchool ? 'Loading Sunday School' : 'Initializing Platform' }}</p>
    </div>

    <div id="cmsPageContent" style="display:none">
        @isset($header)
            <header class="cms-page__header">
                {{ $header }}
            </header>
        @endisset
        <div class="cms-page__body{{ $isCmsNativeShell ? '' : ' portal-module-content' }}">
            {{ $slot }}
        </div>
    </div>

    <script type="application/json" id="cms-boot-data">@json($cmsBootData)</script>
    <script>
        (function () {
            var boot = JSON.parse(document.getElementById('cms-boot-data').textContent);
            window.CMS_CONFIG = boot.config;
            window.CMS_MEDIA_BASE = boot.media_base;
            window.CMS_ADMIN_BASE = boot.admin_base;
            window.CMS_LEGACY_ADMIN_BASE = boot.legacy_admin_base;
            window.CMS_ADMIN_USER = boot.admin_user;
            window.CMS_LOGOUT_URL = boot.logout_url;
            window.CMS_SETTINGS_URL = boot.settings_url;
            window.CMS_USER_UI_PREF = boot.user_ui_pref;
            window.CMS_NAV_ACCESS = boot.nav_access || { rbac_enabled: false, fail_open: true, super_admin_bypass: false, items: {} };
            window.CMS_DASHBOARD_ACCESS = boot.dashboard_access || {
                rbac_enabled: false,
                fail_open: true,
                super_admin_bypass: false,
                sections: {},
                widgets: {},
                quick_actions: {},
                presentation: {
                    title: 'Dashboard',
                    subtitle: 'Live overview from Members, Visitors, and platform activity.',
                    brand_subtitle: boot.brand_subtitle || 'Church Management System',
                    home_route: boot.home_route
                }
            };
            window.CMS_SESSION = boot.session || {};
        })();
    </script>
    <script src="{{ $portalJs }}/rbac-nav.js"></script>
    <script src="{{ $portalJs }}/rbac-dashboard.js"></script>
    <script src="{{ $portalJs }}/shell.js?v={{ is_file(public_path('portal/js/shell.js')) ? filemtime(public_path('portal/js/shell.js')) : 1 }}"></script>
    <script src="{{ $portalJs }}/laravel-shell.js"></script>
    <script src="{{ $portalJs }}/core.js"></script>
    <script src="{{ $portalJs }}/notifications.js?v={{ is_file(public_path('portal/js/notifications.js')) ? filemtime(public_path('portal/js/notifications.js')) : 1 }}"></script>
    <script src="{{ $portalJs }}/session-timeout.js"></script>
</body>
</html>
