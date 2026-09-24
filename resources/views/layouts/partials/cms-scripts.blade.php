@php
    $portalJs = asset('portal/js');
@endphp
    <script type="application/json" id="cms-boot-data">@json($cmsBootData)</script>
    <script>
        (function () {
            var boot = JSON.parse(document.getElementById('cms-boot-data').textContent);
            window.CMS_CONFIG = boot.config;
            window.CMS_MEDIA_BASE = boot.media_base;
            window.CMS_ADMIN_BASE = boot.admin_base;
            window.CMS_DASHBOARD_HANDLER_URL = boot.dashboard_handler_url || (String(boot.admin_base || '').replace(/\/?$/, '/') + 'handlers/dashboard-handler');
            window.CMS_LEGACY_ADMIN_BASE = boot.legacy_admin_base;
            window.CMS_ADMIN_USER = boot.admin_user;
            window.CMS_LOGOUT_URL = boot.logout_url;
            window.CMS_SETTINGS_URL = boot.settings_url || '';
            window.CMS_USER_UI_PREF = boot.user_ui_pref;
            window.CMS_PLATFORM = boot.platform || 'ag';
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
                    subtitle: '',
                    brand_subtitle: (boot.config && boot.config.brand && boot.config.brand.subtitle) || boot.brand_subtitle || '',
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
    <script src="{{ $portalJs }}/core.js?v={{ is_file(public_path('portal/js/core.js')) ? filemtime(public_path('portal/js/core.js')) : 1 }}"></script>
    <script src="{{ $portalJs }}/notifications.js?v={{ is_file(public_path('portal/js/notifications.js')) ? filemtime(public_path('portal/js/notifications.js')) : 1 }}"></script>
    <script src="{{ $portalJs }}/session-timeout.js"></script>
    @stack('scripts')
</body>
</html>
