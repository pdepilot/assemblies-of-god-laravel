/**
 * AG Ikenebgu — Covenant Cookie Banner (index homepage)
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ag_ikenebgu_cookie_consent';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function getStored() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function savePrefs(prefs) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                essential: true,
                analytics: !!prefs.analytics,
                performance: !!prefs.performance,
                personalization: !!prefs.personalization,
                timestamp: Date.now()
            }));
        } catch (e) { /* ignore */ }
        window.dispatchEvent(new CustomEvent('ag:cookie-consent', { detail: prefs }));
    }

    function whenPageReady(callback) {
        function run() {
            setTimeout(callback, reducedMotion ? 200 : 500);
        }

        if (!document.body.classList.contains('preloader-active')) {
            run();
            return;
        }

        var observer = new MutationObserver(function () {
            if (!document.body.classList.contains('preloader-active')) {
                observer.disconnect();
                run();
            }
        });
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        setTimeout(function () {
            observer.disconnect();
            run();
        }, 15000);
    }

    function grandEntrance(breakEl, banner) {
        if (reducedMotion) {
            banner.classList.add('is-visible');
            banner.setAttribute('aria-hidden', 'false');
            return;
        }

        breakEl.classList.add('is-active');
        banner.classList.add('is-breaking');
        banner.setAttribute('aria-hidden', 'false');

        banner.addEventListener('animationend', function onEnd(e) {
            if (e.animationName !== 'agBannerRise') return;
            banner.removeEventListener('animationend', onEnd);
            banner.classList.remove('is-breaking');
            banner.classList.add('is-visible');
        });

        setTimeout(function () {
            breakEl.classList.remove('is-active');
        }, 900);
    }

    function dismissBanner(banner) {
        banner.classList.remove('is-visible', 'is-breaking');
        banner.classList.add('is-dismissed');
        banner.setAttribute('aria-hidden', 'true');
        setTimeout(function () {
            banner.style.display = 'none';
        }, 600);
    }

    function openModal(modal) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('.ag-cookie-modal__close')?.focus();
    }

    function closeModal(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function readModalPrefs(modal) {
        var prefs = { essential: true };
        modal.querySelectorAll('[data-ag-cookie-pref]').forEach(function (input) {
            var key = input.getAttribute('data-ag-cookie-pref');
            if (key !== 'essential') {
                prefs[key] = input.checked;
            }
        });
        return prefs;
    }

    function applyPrefsToModal(modal, prefs) {
        modal.querySelectorAll('[data-ag-cookie-pref]').forEach(function (input) {
            var key = input.getAttribute('data-ag-cookie-pref');
            if (key === 'essential') return;
            input.checked = !!prefs[key];
        });
    }

    function init() {
        if (getStored()) return;

        var breakEl = document.getElementById('agCookieBreak');
        var banner = document.getElementById('agCookieBanner');
        var modal = document.getElementById('agCookieModal');

        if (!breakEl || !banner) return;

        whenPageReady(function () {
            grandEntrance(breakEl, banner);
        });

        banner.addEventListener('click', function (e) {
            var action = e.target.closest('[data-action]');
            if (!action) return;

            var type = action.getAttribute('data-action');

            if (type === 'accept-all') {
                savePrefs({ essential: true, analytics: true, performance: true, personalization: true });
                dismissBanner(banner);
                return;
            }

            if (type === 'essential-only') {
                savePrefs({ essential: true, analytics: false, performance: false, personalization: false });
                dismissBanner(banner);
                return;
            }

            if (type === 'customize' && modal) {
                openModal(modal);
            }
        });

        if (!modal) return;

        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('[data-action="close-modal"]')) {
                closeModal(modal);
            }
        });

        modal.querySelector('[data-action="save-prefs"]')?.addEventListener('click', function () {
            savePrefs(readModalPrefs(modal));
            closeModal(modal);
            dismissBanner(banner);
        });

        modal.querySelector('[data-action="accept-all-modal"]')?.addEventListener('click', function () {
            savePrefs({ essential: true, analytics: true, performance: true, personalization: true });
            closeModal(modal);
            dismissBanner(banner);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal(modal);
            }
        });

        var stored = getStored();
        if (stored) applyPrefsToModal(modal, stored);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
