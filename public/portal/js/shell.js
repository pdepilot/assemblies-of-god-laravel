/* Shell - Sidebar, Topbar, Layout Injection */
(function () {
    'use strict';

    function getBasePath() {
        var depth = parseInt(document.body.getAttribute('data-depth') || '0', 10);
        return depth > 0 ? '../'.repeat(depth) : '';
    }

    function getAdminBase() {
        if (window.CMS_ADMIN_BASE) {
            return String(window.CMS_ADMIN_BASE).replace(/\/?$/, '/');
        }
        return getBasePath();
    }

    function resolveHref(href, base) {
        href = String(href || '');
        if (href.indexOf('http') === 0 || href.charAt(0) === '/') return href;
        if (window.CMS_ADMIN_BASE) {
            return getAdminBase() + href.replace(/^\//, '');
        }
        return base + href;
    }

    function canShowNavItem(navId) {
        try {
            if (window.CMS_NAV_FILTER && typeof window.CMS_NAV_FILTER.canShow === 'function') {
                return window.CMS_NAV_FILTER.canShow(navId);
            }
        } catch (e) { /* fail-open */ }
        return true;
    }

    function isSdtgNavItem(item) {
        var hay = [item && item.id, item && item.label, item && item.href].join(' ').toLowerCase();
        if (!hay.trim()) return false;
        if (hay.indexOf('send down thy glory') !== -1 || hay.indexOf('/sdtg') !== -1) return true;
        return /(^|[^a-z])sdtg([^a-z]|$)/.test(hay);
    }

    function navWithoutSdtg(nav) {
        return (nav || []).reduce(function (out, item) {
            if (isSdtgNavItem(item)) return out;
            if (item && item.type === 'group') {
                var children = (item.children || []).filter(function (child) {
                    return !isSdtgNavItem(child);
                });
                if (!children.length) return out;
                item = Object.assign({}, item, { children: children });
            }
            out.push(item);
            return out;
        }, []);
    }

    function getDashboardHref(base) {
        try {
            var access = window.CMS_DASHBOARD_ACCESS;
            var home = access && access.presentation && access.presentation.home_route
                ? String(access.presentation.home_route)
                : '';
            // Prefer Laravel home_route (absolute URL) whenever present.
            if (home) {
                if (home.indexOf('http') === 0 || home.charAt(0) === '/') {
                    return home;
                }
                return resolveHref(home, base);
            }
        } catch (e) { /* keep default */ }
        // Legacy PHP used admin-dashboard; Laravel route is /admin/dashboard.
        return resolveHref(window.CMS_ADMIN_BASE ? 'dashboard' : 'admin-dashboard', base);
    }

    function buildNav(base, activePage) {
        var html = '';
        CMS_CONFIG.nav.forEach(function (item) {
            if (item.type === 'group') {
                var visibleChildren = (item.children || []).filter(function (child) {
                    return canShowNavItem(child.id);
                });
                if (!visibleChildren.length) {
                    return;
                }
                var groupClass = 'cms-nav-group';
                html += '<div class="' + groupClass + '" data-group="' + item.id + '">';
                html += '<button type="button" class="cms-nav-group__label" aria-expanded="true">';
                html += '<span>' + item.label + '</span>';
                html += '<i class="fas fa-chevron-down chevron" aria-hidden="true"></i>';
                html += '</button><ul class="cms-nav-group__items">';
                visibleChildren.forEach(function (child) {
                    var isActive = child.id === activePage ? ' is-active' : '';
                    html += '<li class="cms-nav-item' + isActive + '">';
                    html += '<a href="' + resolveHref(child.href, base) + '">';
                    html += '<i class="fas ' + child.icon + ' cms-nav-item__icon" aria-hidden="true"></i>';
                    html += '<span>' + child.label + '</span>';
                    if (child.badge) {
                        html += '<span class="cms-nav-item__badge">' + child.badge + '</span>';
                    }
                    html += '</a></li>';
                });
                html += '</ul></div>';
            } else {
                if (!canShowNavItem(item.id)) {
                    return;
                }
                var active = item.id === activePage ? ' is-active' : '';
                html += '<div class="cms-nav-group"><ul class="cms-nav-group__items">';
                html += '<li class="cms-nav-item' + active + '">';
                html += '<a href="' + (item.id === 'dashboard' ? getDashboardHref(base) : resolveHref(item.href, base)) + '">';
                html += '<i class="fas ' + item.icon + ' cms-nav-item__icon" aria-hidden="true"></i>';
                html += '<span>' + item.label + '</span></a></li></ul></div>';
            }
        });
        return html;
    }

    function buildNotifications() {
        var items = (CMS_CONFIG.notifications || []).map(function (n) {
            return '<div class="cms-notify-item">' +
                '<div class="cms-notify-item__icon cms-notify-item__icon--' + n.icon + '"><i class="fas fa-bell" aria-hidden="true"></i></div>' +
                '<div class="cms-notify-item__text"><strong>' + n.title + '</strong><span>' + n.text + '</span>' +
                '<div class="cms-notify-item__time">' + n.time + '</div></div></div>';
        }).join('');
        return '<div class="cms-notify-list" id="cmsNotifyList">' +
            (items || '<div class="cms-notify-empty">No notifications yet. Activity from Members and Visitors will appear here.</div>') +
            '</div>';
    }

    function injectShell() {
        var base = getBasePath();
        var activePage = document.body.getAttribute('data-page') || 'dashboard';
        var content = document.getElementById('cmsPageContent');
        if (!content) return;
        if (window.CMS_CONFIG) {
            CMS_CONFIG.nav = navWithoutSdtg(CMS_CONFIG.nav);
        }

        var brand = CMS_CONFIG.brand || {};
        var brandSubtitle = brand.subtitle || '';
        try {
            if (window.CMS_DASHBOARD_ACCESS && CMS_DASHBOARD_ACCESS.presentation && CMS_DASHBOARD_ACCESS.presentation.brand_subtitle && CMS_DASHBOARD_ACCESS.rbac_enabled && !CMS_DASHBOARD_ACCESS.super_admin_bypass) {
                brandSubtitle = CMS_DASHBOARD_ACCESS.presentation.brand_subtitle;
            }
        } catch (e) { /* keep default */ }
        var depth = parseInt(document.body.getAttribute('data-depth') || '0', 10);
        var mediaPrefix = '../'.repeat(depth + 1);
        var logoVideo = brand.logo_video_url
            || (brand.logo_video_path ? (window.CMS_MEDIA_BASE || mediaPrefix).replace(/\/?$/, '/') + String(brand.logo_video_path).replace(/^\//, '') : '')
            || ((window.CMS_MEDIA_BASE || mediaPrefix).replace(/\/?$/, '/') + 'videos/3D_video.mp4');
        var searchPlaceholder = brand.search_placeholder || 'Search...';
        var settingsUrl = window.CMS_SETTINGS_URL || '';
        var settingsLink = settingsUrl
            ? '<a href="' + settingsUrl + '" role="menuitem"><i class="fas fa-gear"></i> Settings</a>'
            : '';

        var shell = document.createElement('div');
        shell.className = 'cms-layout';
        shell.innerHTML =
            '<div class="cms-sidebar-overlay" id="cmsSidebarOverlay" aria-hidden="true"></div>' +
            '<aside class="cms-sidebar" id="cmsSidebar" aria-label="Main navigation">' +
                '<div class="cms-sidebar__brand">' +
                    '<div class="cms-sidebar__logo"><video src="' + logoVideo + '" autoplay muted loop playsinline aria-hidden="true"></video></div>' +
                    '<div class="cms-sidebar__brand-text"><strong>' + (brand.name || '') + '</strong><span>' + brandSubtitle + '</span></div>' +
                '</div>' +
                '<nav class="cms-sidebar__nav" role="navigation">' + buildNav(base, activePage) + '</nav>' +
                '<div class="cms-sidebar__footer">' +
                    '<button type="button" class="cms-sidebar__collapse" id="cmsSidebarCollapse" aria-label="Collapse sidebar">' +
                        '<i class="fas fa-angles-left" aria-hidden="true"></i><span>Collapse</span>' +
                    '</button>' +
                '</div>' +
            '</aside>' +
            '<div class="cms-main">' +
                '<header class="cms-topbar" role="banner">' +
                    '<button type="button" class="cms-topbar__menu" id="cmsMobileMenu" aria-label="Open menu"><i class="fas fa-bars"></i></button>' +
                    '<div class="cms-topbar__search" role="search">' +
                        '<i class="fas fa-search" aria-hidden="true"></i>' +
                        '<input type="search" id="cmsGlobalSearch" placeholder="' + searchPlaceholder + '" aria-label="Global search">' +
                    '</div>' +
                    '<div class="cms-topbar__actions">' +
                        '<div class="cms-theme-picker">' +
                            '<label for="cmsThemeSelect" class="cms-theme-picker__label"><i class="fas fa-palette" aria-hidden="true"></i> Theme</label>' +
                            '<select id="cmsThemeSelect" class="cms-theme-picker__select" aria-label="Select admin theme">' +
                                '<option value="gold">Gold (Default)</option>' +
                                '<option value="ocean">Ocean Blue</option>' +
                                '<option value="emerald">Emerald</option>' +
                                '<option value="royal">Royal Purple</option>' +
                                '<option value="sunset">Sunset Orange</option>' +
                            '</select>' +
                            '<label for="cmsModeSelect" class="cms-theme-picker__label"><i class="fas fa-circle-half-stroke" aria-hidden="true"></i> Mode</label>' +
                            '<select id="cmsModeSelect" class="cms-theme-picker__select" aria-label="Select admin color mode">' +
                                '<option value="dark">Dark</option>' +
                                '<option value="light">Light</option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="cms-profile" style="position:relative">' +
                            '<button type="button" class="cms-topbar__btn" id="cmsNotifyBtn" aria-label="Notifications" aria-expanded="false">' +
                                '<i class="fas fa-bell"></i><span class="cms-dot" aria-hidden="true"></span>' +
                            '</button>' +
                            '<div class="cms-notify-panel" id="cmsNotifyPanel" role="region" aria-label="Notifications">' +
                                '<div class="cms-notify-panel__head"><span>Notifications</span><button type="button" class="cms-btn cms-btn--sm cms-btn--ghost" id="cmsMarkRead">Mark all read</button></div>' +
                                buildNotifications() +
                            '</div>' +
                        '</div>' +
                        '<div class="cms-profile" id="cmsProfile">' +
                            '<button type="button" class="cms-profile__trigger" id="cmsProfileTrigger" aria-expanded="false">' +
                                '<div class="cms-profile__avatar" aria-hidden="true">AN</div>' +
                                '<div class="cms-profile__info"><strong>Admin</strong><span>Super Administrator</span></div>' +
                                '<i class="fas fa-chevron-down cms-profile__chevron" aria-hidden="true"></i>' +
                            '</button>' +
                            '<div class="cms-dropdown" id="cmsProfileDropdown" role="menu">' +
                                '<button type="button" role="menuitem" id="cmsOpenAccount"><i class="fas fa-user-gear"></i> Login Details</button>' +
                                settingsLink +
                                '<div class="cms-dropdown__divider"></div>' +
                                '<button type="button" role="menuitem" id="cmsSignOutBtn"><i class="fas fa-right-from-bracket"></i> Sign Out</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</header>' +
                '<main class="cms-page" id="cmsPageMain" role="main"></main>' +
            '</div>';

        document.body.insertBefore(shell, content);
        document.getElementById('cmsPageMain').appendChild(content);
        content.style.display = 'block';
        content.removeAttribute('id');

        if (!document.getElementById('cmsAccountModal')) {
            var modal = document.createElement('div');
            modal.id = 'cmsAccountModal';
            modal.className = 'cms-modal';
            modal.setAttribute('hidden', 'hidden');
            modal.innerHTML =
                '<div class="cms-modal__backdrop" data-close-account></div>' +
                '<div class="cms-modal__dialog cms-modal__dialog--sm" role="dialog" aria-labelledby="cmsAccountTitle">' +
                '<div class="cms-modal__head"><h2 class="cms-modal__title" id="cmsAccountTitle">Login Details</h2>' +
                '<button type="button" class="cms-modal__close" data-close-account aria-label="Close">&times;</button></div>' +
                '<form id="cmsAccountForm" class="cms-modal__body">' +
                '<p class="cms-muted" style="margin-top:0">Email is shared with Financial ERP. The password here is for Church Management only — changing it does not change your Financial ERP password.</p>' +
                '<div class="cms-form-grid">' +
                '<div class="cms-field"><label for="cmsAccName">Full name</label><input id="cmsAccName" name="full_name" required></div>' +
                '<div class="cms-field"><label for="cmsAccEmail">Login email</label><input id="cmsAccEmail" type="email" name="email" required></div>' +
                '<div class="cms-field"><label for="cmsAccPhone">Phone</label><input id="cmsAccPhone" name="phone"></div>' +
                '<div class="cms-field"><label for="cmsAccRecovery">Recovery email</label><input id="cmsAccRecovery" type="email" name="recovery_email"></div>' +
                '<div class="cms-field" style="grid-column:1/-1"><label for="cmsAccCurrent">Current Church Management password</label><input id="cmsAccCurrent" type="password" name="current_password" required autocomplete="current-password"></div>' +
                '<div class="cms-field"><label for="cmsAccNew">New Church Management password</label><input id="cmsAccNew" type="password" name="new_password" autocomplete="new-password" placeholder="Leave blank to keep"></div>' +
                '<div class="cms-field"><label for="cmsAccConfirm">Confirm new password</label><input id="cmsAccConfirm" type="password" name="confirm_password" autocomplete="new-password"></div>' +
                '</div>' +
                '<div class="cms-modal__foot">' +
                '<button type="button" class="cms-btn cms-btn--ghost" data-close-account>Cancel</button>' +
                '<button type="submit" class="cms-btn cms-btn--primary">Save Login Details</button>' +
                '</div></form></div>';
            document.body.appendChild(modal);
        }

        document.querySelectorAll('.cms-sidebar__logo video').forEach(function (v) {
            v.muted = true;
            v.play().catch(function () {});
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectShell);
    } else {
        injectShell();
    }
})();