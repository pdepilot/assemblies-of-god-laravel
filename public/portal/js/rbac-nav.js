(function () {
    'use strict';

    /**
     * Phase 7 — fail-open sidebar filter.
     * If RBAC is disabled, data is missing, or an error occurs, show all nav items.
     */
    window.CMS_NAV_FILTER = {
        canShow: function (navId) {
            try {
                var access = window.CMS_NAV_ACCESS;
                if (!access || access.fail_open || !access.rbac_enabled || access.super_admin_bypass) {
                    return true;
                }
                if (!access.items || typeof access.items !== 'object') {
                    return true;
                }
                if (typeof access.items[navId] === 'undefined') {
                    return true;
                }
                return !!access.items[navId];
            } catch (e) {
                return true;
            }
        }
    };
})();
