@php
    $shellPlatform = \App\Support\RbacPlatform::AG;
    $admin = auth('admin')->user();
    abort_unless($admin instanceof \App\Models\Admin, 401);

    $portalNav = app(\App\Services\Portal\PortalNavService::class);
    $boot = app(\App\Services\Portal\CmsShellBootService::class);
    $cmsBootData = $boot->bootData($admin, $shellPlatform);
    $activePage = $portalPage ?? $portalNav->resolveActivePage(null, $shellPlatform);
    $isSundaySchool = $activePage === 'sunday-school' || str_starts_with(request()->path(), 'admin/sunday-school');
    $isSsLegacyShell = $isSundaySchool && request()->routeIs('ss.analytics.index');
    $isSettingsShell = request()->routeIs('settings.*');
    $isCmsNativeShell = $isSsLegacyShell || $isSettingsShell;
@endphp
@include('layouts.partials.cms-head')
@include('layouts.partials.cms-body')
@include('layouts.partials.cms-scripts')
