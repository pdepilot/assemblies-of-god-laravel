(function () {
    'use strict';

    /**
     * Role-based dashboard visibility — fail-open when RBAC disabled.
     */
    window.CMS_DASHBOARD_FILTER = {
        access: function () {
            return window.CMS_DASHBOARD_ACCESS || null;
        },

        isOpen: function () {
            var access = this.access();
            return !access || access.fail_open || !access.rbac_enabled || access.super_admin_bypass;
        },

        canShowSection: function (sectionId) {
            if (this.isOpen()) return true;
            var access = this.access();
            if (!access.sections || typeof access.sections[sectionId] === 'undefined') return true;
            return !!access.sections[sectionId];
        },

        canShowWidget: function (widgetId) {
            if (this.isOpen()) return true;
            var access = this.access();
            if (!access.widgets || typeof access.widgets[widgetId] === 'undefined') return true;
            return !!access.widgets[widgetId];
        },

        canShowQuickAction: function (actionId) {
            if (this.isOpen()) return true;
            var access = this.access();
            if (!access.quick_actions || typeof access.quick_actions[actionId] === 'undefined') return true;
            return !!access.quick_actions[actionId];
        },

        apply: function () {
            try {
                document.querySelectorAll('[data-widget-section]').forEach(function (el) {
                    var sectionId = el.getAttribute('data-widget-section');
                    if (sectionId && !CMS_DASHBOARD_FILTER.canShowSection(sectionId)) {
                        el.style.display = 'none';
                        el.setAttribute('hidden', 'hidden');
                    }
                });

                document.querySelectorAll('[data-widget]').forEach(function (el) {
                    var widgetId = el.getAttribute('data-widget');
                    if (widgetId && !CMS_DASHBOARD_FILTER.canShowWidget(widgetId)) {
                        el.style.display = 'none';
                        el.setAttribute('hidden', 'hidden');
                    }
                });

                document.querySelectorAll('[data-quick-action]').forEach(function (el) {
                    var actionId = el.getAttribute('data-quick-action');
                    if (actionId && !CMS_DASHBOARD_FILTER.canShowQuickAction(actionId)) {
                        el.style.display = 'none';
                        el.setAttribute('hidden', 'hidden');
                    }
                });

                document.querySelectorAll('[data-dash-action]').forEach(function (el) {
                    var moduleKey = el.getAttribute('data-dash-action');
                    if (!moduleKey || CMS_DASHBOARD_FILTER.isOpen()) return;
                    var access = CMS_DASHBOARD_FILTER.access();
                    var navItems = access && access.nav_items ? access.nav_items : null;
                    if (navItems && typeof navItems[moduleKey] !== 'undefined' && !navItems[moduleKey]) {
                        el.style.display = 'none';
                        el.setAttribute('hidden', 'hidden');
                    }
                });

                var access = this.access();
                if (access && access.presentation) {
                    var titleEl = document.getElementById('dashTitle');
                    var subtitleEl = document.getElementById('dashSubtitle');
                    if (titleEl && access.presentation.title) titleEl.textContent = access.presentation.title;
                    if (subtitleEl && access.presentation.subtitle) subtitleEl.textContent = access.presentation.subtitle;
                }
            } catch (e) {
                /* fail-open */
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.CMS_DASHBOARD_FILTER) {
            window.CMS_DASHBOARD_FILTER.apply();
        }
    });
})();
