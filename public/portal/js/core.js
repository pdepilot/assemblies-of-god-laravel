/* CMS Core Interactions */
(function () {
    'use strict';

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    function hideLoader() {
        var loader = $('#cmsLoader');
        if (loader) {
            setTimeout(function () { loader.classList.add('is-hidden'); }, 120);
        }
    }

    function initSidebar() {
        var sidebar = $('#cmsSidebar');
        var collapseBtn = $('#cmsSidebarCollapse');
        var mobileBtn = $('#cmsMobileMenu');
        var overlay = $('#cmsSidebarOverlay');

        if (collapseBtn && sidebar) {
            collapseBtn.addEventListener('click', function () {
                sidebar.classList.toggle('is-collapsed');
                document.body.classList.toggle('sidebar-collapsed');
                var icon = collapseBtn.querySelector('i');
                if (icon) {
                    icon.className = sidebar.classList.contains('is-collapsed')
                        ? 'fas fa-angles-right' : 'fas fa-angles-left';
                }
            });
        }

        if (mobileBtn && sidebar && overlay) {
            mobileBtn.addEventListener('click', function () {
                sidebar.classList.add('is-mobile-open');
                overlay.classList.add('is-visible');
            });
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('is-mobile-open');
                overlay.classList.remove('is-visible');
            });
        }

        $$('.cms-nav-group__label').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.closest('.cms-nav-group');
                if (group) {
                    group.classList.toggle('is-collapsed');
                    btn.setAttribute('aria-expanded', !group.classList.contains('is-collapsed'));
                }
            });
        });
    }

    function initDropdowns() {
        var profile = $('#cmsProfile');
        var profileTrigger = $('#cmsProfileTrigger');
        var profileDropdown = $('#cmsProfileDropdown');
        var notifyBtn = $('#cmsNotifyBtn');
        var notifyPanel = $('#cmsNotifyPanel');

        if (profileTrigger && profileDropdown) {
            profileTrigger.addEventListener('click', function (e) {
                e.stopPropagation();
                profile.classList.toggle('is-open');
                profileDropdown.classList.toggle('is-open');
                profileTrigger.setAttribute('aria-expanded', profile.classList.contains('is-open'));
                if (notifyPanel) notifyPanel.classList.remove('is-open');
            });
        }

        if (notifyBtn && notifyPanel) {
            notifyBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notifyPanel.classList.toggle('is-open');
                notifyBtn.setAttribute('aria-expanded', notifyPanel.classList.contains('is-open'));
                if (profile) {
                    profile.classList.remove('is-open');
                    if (profileDropdown) profileDropdown.classList.remove('is-open');
                }
            });
        }

        document.addEventListener('click', function () {
            if (profile) profile.classList.remove('is-open');
            if (profileDropdown) profileDropdown.classList.remove('is-open');
            if (notifyPanel) notifyPanel.classList.remove('is-open');
        });

        var markRead = $('#cmsMarkRead');
        if (markRead) {
            markRead.addEventListener('click', function () {
                if (window.CMSNotifications && CMSNotifications.markAllRead) {
                    CMSNotifications.markAllRead();
                    return;
                }
                var dot = notifyBtn && notifyBtn.querySelector('.cms-dot');
                if (dot) dot.style.display = 'none';
                showToast('All notifications marked as read', 'info');
            });
        }
    }

    function initTabs() {
        $$('[data-tabs]').forEach(function (container) {
            var tabs = container.querySelectorAll('.cms-tab');
            var panels = container.parentElement.querySelectorAll('.cms-tab-panel');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = tab.getAttribute('data-tab');
                    tabs.forEach(function (t) { t.classList.remove('is-active'); });
                    panels.forEach(function (p) { p.classList.remove('is-active'); });
                    tab.classList.add('is-active');
                    var panel = container.parentElement.querySelector('[data-panel="' + target + '"]');
                    if (panel) panel.classList.add('is-active');
                });
            });
        });
    }

    function initCounters() {
        $$('[data-count]').forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-count'));
            var suffix = el.getAttribute('data-suffix') || '';
            var prefix = el.getAttribute('data-prefix') || '';
            var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
            var duration = 1800;
            var start = 0;
            var startTime = null;

            function step(ts) {
                if (!startTime) startTime = ts;
                var progress = Math.min((ts - startTime) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                var current = start + (target - start) * eased;
                el.textContent = prefix + current.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + suffix;
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    function showToast(message, type) {
        var container = $('#cmsToasts');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cmsToasts';
            container.className = 'cms-toasts';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        var tone = type || 'info';
        var icon = 'info-circle';
        if (tone === 'success') icon = 'check-circle';
        else if (tone === 'error' || tone === 'danger') icon = 'triangle-exclamation';
        else if (tone === 'warning') icon = 'exclamation-circle';

        var toast = document.createElement('div');
        toast.className = 'cms-toast cms-toast--' + (tone === 'danger' ? 'error' : tone);
        toast.innerHTML = '<i class="fas fa-' + icon + '" aria-hidden="true"></i><span></span>';
        toast.querySelector('span').textContent = String(message || '');
        container.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 4200);
    }

    /**
     * In-app confirm dialog (replaces browser confirm / "localhost says…").
     * @param {string|object} options message string or { title, message, confirmLabel, cancelLabel, tone }
     * @returns {Promise<boolean>}
     */
    function cmsConfirm(options) {
        var opts = typeof options === 'string' ? { message: options } : (options || {});
        var title = opts.title || 'Please confirm';
        var message = opts.message || 'Are you sure you want to continue?';
        var confirmLabel = opts.confirmLabel || 'Confirm';
        var cancelLabel = opts.cancelLabel || 'Cancel';
        var tone = opts.tone || 'danger';

        return new Promise(function (resolve) {
            var existing = document.getElementById('cmsConfirmModal');
            if (existing) existing.remove();

            var modal = document.createElement('div');
            modal.id = 'cmsConfirmModal';
            modal.className = 'cms-modal is-open cms-confirm-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'cmsConfirmTitle');

            modal.innerHTML =
                '<div class="cms-modal__backdrop" data-cms-confirm-cancel></div>' +
                '<div class="cms-modal__dialog cms-modal__dialog--sm cms-confirm-modal__dialog">' +
                '  <div class="cms-modal__head">' +
                '    <h2 id="cmsConfirmTitle" class="cms-modal__title"></h2>' +
                '    <button type="button" class="cms-modal__close" data-cms-confirm-cancel aria-label="Close">&times;</button>' +
                '  </div>' +
                '  <div class="cms-modal__body"><p class="cms-confirm-modal__message"></p></div>' +
                '  <div class="cms-modal__foot">' +
                '    <button type="button" class="cms-btn cms-btn--ghost" data-cms-confirm-cancel></button>' +
                '    <button type="button" class="cms-btn cms-confirm-modal__ok" data-cms-confirm-ok></button>' +
                '  </div>' +
                '</div>';

            modal.querySelector('#cmsConfirmTitle').textContent = title;
            modal.querySelector('.cms-confirm-modal__message').textContent = message;
            modal.querySelector('[data-cms-confirm-cancel].cms-btn').textContent = cancelLabel;
            var okBtn = modal.querySelector('[data-cms-confirm-ok]');
            okBtn.textContent = confirmLabel;
            okBtn.classList.add(tone === 'danger' || tone === 'error' ? 'cms-btn--danger' : 'cms-btn--primary');

            document.body.appendChild(modal);
            document.body.classList.add('cms-modal-open');
            okBtn.focus();

            function finish(result) {
                document.removeEventListener('keydown', onKey);
                document.body.classList.remove('cms-modal-open');
                modal.remove();
                resolve(result);
            }

            function onKey(e) {
                if (e.key === 'Escape') finish(false);
                if (e.key === 'Enter') finish(true);
            }

            modal.querySelectorAll('[data-cms-confirm-cancel]').forEach(function (el) {
                el.addEventListener('click', function () { finish(false); });
            });
            okBtn.addEventListener('click', function () { finish(true); });
            document.addEventListener('keydown', onKey);
        });
    }

    function initConfirmBindings() {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.getAttribute('data-confirm-accepted') === '1') {
                form.removeAttribute('data-confirm-accepted');
                return;
            }

            var submitter = e.submitter || null;
            var msg = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
            if (!msg) return;

            e.preventDefault();
            e.stopPropagation();

            var title = (submitter && submitter.getAttribute('data-confirm-title'))
                || form.getAttribute('data-confirm-title')
                || 'Please confirm';
            var tone = (submitter && submitter.getAttribute('data-confirm-tone'))
                || form.getAttribute('data-confirm-tone')
                || 'danger';
            var ok = (submitter && submitter.getAttribute('data-confirm-ok'))
                || form.getAttribute('data-confirm-ok')
                || 'Confirm';

            cmsConfirm({
                title: title,
                message: msg,
                confirmLabel: ok,
                cancelLabel: form.getAttribute('data-confirm-cancel') || 'Cancel',
                tone: tone
            }).then(function (accepted) {
                if (!accepted) return;
                form.setAttribute('data-confirm-accepted', '1');
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter || undefined);
                } else {
                    form.submit();
                }
            });
        }, true);

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el || el.tagName === 'FORM') return;
            if (el.tagName === 'BUTTON' && el.getAttribute('type') === 'submit') return;
            if (el.tagName === 'INPUT' && el.getAttribute('type') === 'submit') return;

            var msg = el.getAttribute('data-confirm');
            if (!msg) return;

            e.preventDefault();
            e.stopPropagation();

            cmsConfirm({
                title: el.getAttribute('data-confirm-title') || 'Please confirm',
                message: msg,
                confirmLabel: el.getAttribute('data-confirm-ok') || 'Confirm',
                cancelLabel: el.getAttribute('data-confirm-cancel') || 'Cancel',
                tone: el.getAttribute('data-confirm-tone') || 'danger'
            }).then(function (accepted) {
                if (!accepted) return;
                if (el.tagName === 'A' && el.getAttribute('href')) {
                    window.location.href = el.getAttribute('href');
                    return;
                }
                el.dispatchEvent(new CustomEvent('cms:confirmed', { bubbles: true }));
            });
        }, true);
    }

    function initGlobalSearch() {
        var input = $('#cmsGlobalSearch');
        if (!input) return;
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && input.value.trim()) {
                showToast('Search: "' + input.value.trim() + '" — backend integration pending', 'info');
            }
        });
    }

    function initExportButtons() {
        $$('[data-export]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showToast('Export to ' + btn.getAttribute('data-export') + ' — ready for PHP backend', 'success');
            });
        });
    }

    function initThemePicker() {
        var themeSelect = $('#cmsThemeSelect');
        var modeSelect = $('#cmsModeSelect');
        var themeStorageKey = 'cms_theme';
        var modeStorageKey = 'cms_mode';
        var themes = ['gold', 'ocean', 'emerald', 'royal', 'sunset'];
        var modes = ['dark', 'light'];

        function applyTheme(theme) {
            var next = themes.indexOf(theme) >= 0 ? theme : 'gold';
            document.body.setAttribute('data-theme', next);
            return next;
        }

        function applyMode(mode) {
            var next = modes.indexOf(mode) >= 0 ? mode : 'dark';
            document.body.setAttribute('data-mode', next);
            return next;
        }

        function persistServer(theme, mode) {
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var token = tokenMeta ? tokenMeta.getAttribute('content') : '';
            if (!token || !window.CMS_ADMIN_BASE) return;

            var body = new FormData();
            body.append('action', 'save_ui_preferences');
            body.append('csrf_token', token);
            body.append('theme', theme);
            body.append('mode', mode);

            fetch(String(window.CMS_ADMIN_BASE).replace(/\/?$/, '/') + 'handlers/session-handler.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            }).catch(function () { /* keep local preference even if request fails */ });
        }

        var savedTheme = 'gold';
        try {
            savedTheme = localStorage.getItem(themeStorageKey) || 'gold';
        } catch (e) { /* ignore storage errors */ }
        if (window.CMS_USER_UI_PREF && typeof window.CMS_USER_UI_PREF === 'object' && window.CMS_USER_UI_PREF.theme) {
            savedTheme = String(window.CMS_USER_UI_PREF.theme);
        }
        var activeTheme = applyTheme(savedTheme);

        var savedMode = 'dark';
        try {
            savedMode = localStorage.getItem(modeStorageKey) || 'dark';
        } catch (e) { /* ignore storage errors */ }
        if (window.CMS_USER_UI_PREF && typeof window.CMS_USER_UI_PREF === 'object' && window.CMS_USER_UI_PREF.mode) {
            savedMode = String(window.CMS_USER_UI_PREF.mode);
        }
        var activeMode = applyMode(savedMode);

        try {
            localStorage.setItem(themeStorageKey, activeTheme);
            localStorage.setItem(modeStorageKey, activeMode);
        } catch (e) { /* ignore storage errors */ }

        if (themeSelect) {
            themeSelect.value = activeTheme;
            themeSelect.addEventListener('change', function () {
                var chosen = applyTheme(themeSelect.value);
                try {
                    localStorage.setItem(themeStorageKey, chosen);
                } catch (e) { /* ignore storage errors */ }
                persistServer(chosen, modeSelect ? modeSelect.value : activeMode);
                showToast('Theme changed to ' + chosen.charAt(0).toUpperCase() + chosen.slice(1), 'success');
            });
        }

        if (modeSelect) {
            modeSelect.value = activeMode;
            modeSelect.addEventListener('change', function () {
                var chosenMode = applyMode(modeSelect.value);
                try {
                    localStorage.setItem(modeStorageKey, chosenMode);
                } catch (e) { /* ignore storage errors */ }
                persistServer(themeSelect ? themeSelect.value : activeTheme, chosenMode);
                showToast('Mode changed to ' + chosenMode.charAt(0).toUpperCase() + chosenMode.slice(1), 'success');
            });
        }
    }

    function sessionHandlerUrl() {
        return String(window.CMS_ADMIN_BASE || '').replace(/\/?$/, '/') + 'handlers/session-handler.php';
    }

    function readCsrfToken() {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return (tokenMeta && tokenMeta.content) || '';
    }

    function initAccountModal() {
        var openBtn = $('#cmsOpenAccount');
        var modal = $('#cmsAccountModal');
        var form = $('#cmsAccountForm');
        if (!openBtn || !modal || !form) return;

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('hidden', 'hidden');
            document.body.classList.remove('cms-modal-open');
        }

        function openModal() {
            var profile = $('#cmsProfile');
            var profileDropdown = $('#cmsProfileDropdown');
            if (profile) profile.classList.remove('is-open');
            if (profileDropdown) profileDropdown.classList.remove('is-open');

            fetch(sessionHandlerUrl() + '?action=my_account', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (d) {
                    if (!d.success) throw new Error(d.message || 'Unable to load account.');
                    if (d.csrf_token) {
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.content = d.csrf_token;
                    }
                    var a = d.account || {};
                    var set = function (id, val) {
                        var el = $(id);
                        if (el) el.value = val || '';
                    };
                    set('cmsAccName', a.full_name);
                    set('cmsAccEmail', a.email);
                    set('cmsAccPhone', a.phone);
                    set('cmsAccRecovery', a.recovery_email);
                    set('cmsAccCurrent', '');
                    set('cmsAccNew', '');
                    set('cmsAccConfirm', '');
                    modal.removeAttribute('hidden');
                    modal.classList.add('is-open');
                    document.body.classList.add('cms-modal-open');
                })
                .catch(function (err) {
                    showToast(err.message || 'Unable to load account.', 'error');
                });
        }

        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });

        modal.querySelectorAll('[data-close-account]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            fd.append('action', 'update_my_account');
            fd.append('csrf_token', readCsrfToken());
            fetch(sessionHandlerUrl(), {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
                .then(function (pack) {
                    if (!pack.ok || !pack.d.success) throw new Error((pack.d && pack.d.message) || 'Save failed.');
                    showToast(pack.d.message || 'Login details updated.', 'success');
                    var nameEl = document.querySelector('#cmsProfileTrigger .cms-profile__info strong');
                    if (nameEl && pack.d.account && pack.d.account.full_name) {
                        nameEl.textContent = pack.d.account.full_name;
                    }
                    var avatar = document.querySelector('#cmsProfileTrigger .cms-profile__avatar');
                    if (avatar && pack.d.account && pack.d.account.full_name) {
                        avatar.textContent = String(pack.d.account.full_name).charAt(0).toUpperCase();
                    }
                    closeModal();
                })
                .catch(function (err) {
                    showToast(err.message || 'Save failed.', 'error');
                });
        });
    }

    function initForms() {
        $$('[data-cms-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                showToast('Changes saved — awaiting backend integration', 'success');
            });
        });
    }

    window.CMS = { showToast: showToast, confirm: cmsConfirm, initCounters: initCounters };

    function init() {
        initSidebar();
        initDropdowns();
        initThemePicker();
        initTabs();
        initCounters();
        initGlobalSearch();
        initExportButtons();
        initAccountModal();
        initForms();
        initConfirmBindings();
        hideLoader();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
