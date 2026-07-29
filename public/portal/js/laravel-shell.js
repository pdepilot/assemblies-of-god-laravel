/**
 * Laravel admin shell patches after legacy shell.js injects layout.
 */
(function () {
    'use strict';

    function patchMedia() {
        var base = window.CMS_MEDIA_BASE || '';
        if (!base) return;
        document.querySelectorAll('.cms-sidebar__logo video').forEach(function (video) {
            video.src = base.replace(/\/?$/, '/') + 'videos/3D_video.mp4';
            video.muted = true;
            video.play().catch(function () {});
        });
    }

    function patchProfile() {
        var user = window.CMS_ADMIN_USER || {};
        var name = user.name || 'Administrator';
        var role = user.role || 'Admin';
        var initial = String(name).charAt(0).toUpperCase() || 'A';

        var avatar = document.querySelector('#cmsProfileTrigger .cms-profile__avatar');
        var nameEl = document.querySelector('#cmsProfileTrigger .cms-profile__info strong');
        var roleEl = document.querySelector('#cmsProfileTrigger .cms-profile__info span');
        if (avatar) avatar.textContent = initial;
        if (nameEl) nameEl.textContent = name;
        if (roleEl) roleEl.textContent = role;
    }

    function submitLogout() {
        var form = document.getElementById('cmsLogoutForm');
        if (form) {
            form.submit();
            return;
        }
        if (window.CMS_LOGOUT_URL) {
            window.location.href = window.CMS_LOGOUT_URL;
        }
    }

    function patchLogout() {
        var btn = document.getElementById('cmsSignOutBtn');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                submitLogout();
            });
        }

        var dropdown = document.getElementById('cmsProfileDropdown');
        if (!dropdown) return;

        dropdown.querySelectorAll('a').forEach(function (link) {
            var text = (link.textContent || '').trim();
            if (text.indexOf('Sign Out') !== -1) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    submitLogout();
                });
            }
            if (text.indexOf('Settings') !== -1) {
                link.href = window.CMS_SETTINGS_URL || (String(window.CMS_ADMIN_BASE || '').replace(/\/?$/, '/') + 'settings');
            }
        });
    }

    function patchActiveNav() {
        var active = document.body.getAttribute('data-page') || '';
        if (!active) return;

        document.querySelectorAll('.cms-nav-item.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });

        var matched = false;
        document.querySelectorAll('.cms-nav-item').forEach(function (item) {
            var link = item.querySelector('a');
            if (!link) return;
            var idMatch = false;
            // Prefer data-page id from body — shell marks via class at build time already
            // Re-apply from body attribute by scanning CMS_CONFIG
        });

        if (window.CMS_CONFIG && Array.isArray(window.CMS_CONFIG.nav)) {
            window.CMS_CONFIG.nav.forEach(function (group) {
                var children = group.type === 'group' ? (group.children || []) : [group];
                children.forEach(function (child) {
                    if (child.id !== active) return;
                    document.querySelectorAll('.cms-nav-item a').forEach(function (a) {
                        if ((a.getAttribute('href') || '') === (child.href || '')) {
                            var li = a.closest('.cms-nav-item');
                            if (li) {
                                li.classList.add('is-active');
                                matched = true;
                                var groupEl = li.closest('.cms-nav-group');
                                if (groupEl) groupEl.classList.remove('is-collapsed');
                            }
                        }
                    });
                });
            });
        }

        if (!matched && active === 'dashboard') {
            var dash = document.querySelector('.cms-nav-item a[href*="dashboard"]');
            if (dash) {
                var li = dash.closest('.cms-nav-item');
                if (li) li.classList.add('is-active');
            }
        }
    }

    function init() {
        patchMedia();
        patchProfile();
        patchLogout();
        patchActiveNav();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(init, 80);
        });
    } else {
        setTimeout(init, 80);
    }
})();
