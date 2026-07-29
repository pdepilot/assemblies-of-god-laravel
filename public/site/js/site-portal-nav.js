/**
 * Site Portal Nav — enhances cross-site navbar buttons with animated portal UI
 */
(function () {
    'use strict';

    var SELECTORS = '.btn-sdtg-nav, .btn--site-switch';

    function createEl(className, attrs) {
        var el = document.createElement('span');
        el.className = className;
        if (attrs) {
            Object.keys(attrs).forEach(function (key) {
                el.setAttribute(key, attrs[key]);
            });
        }
        return el;
    }

    function variantFor(btn) {
        if (btn.classList.contains('btn-sdtg-nav')) return 'sdtg';
        if (btn.classList.contains('btn--site-switch')) return 'ag';
        return 'sdtg';
    }

    function chipLabel(variant) {
        return variant === 'sdtg' ? 'Enter Crusade' : 'Church Home';
    }

    function enhanceButton(btn) {
        if (!btn || btn.classList.contains('site-portal-btn--ready')) return;

        var variant = variantFor(btn);
        btn.classList.add('site-portal-btn', 'site-portal-btn--' + variant, 'site-portal-btn--ready');

        var content = createEl('site-portal-btn__content');
        while (btn.firstChild) {
            content.appendChild(btn.firstChild);
        }

        btn.appendChild(createEl('site-portal-btn__halo', { 'aria-hidden': 'true' }));
        btn.appendChild(createEl('site-portal-btn__aurora', { 'aria-hidden': 'true' }));
        btn.appendChild(createEl('site-portal-btn__ripple', { 'aria-hidden': 'true' }));
        btn.appendChild(createEl('site-portal-btn__beacon', { 'aria-hidden': 'true' }));

        for (var i = 1; i <= 6; i++) {
            btn.appendChild(createEl('site-portal-btn__trail site-portal-btn__trail--' + i, { 'aria-hidden': 'true' }));
        }

        var chip = createEl('site-portal-btn__chip', { 'aria-hidden': 'true' });
        chip.textContent = chipLabel(variant);
        btn.appendChild(chip);
        btn.appendChild(content);
    }

    function init() {
        document.querySelectorAll(SELECTORS).forEach(enhanceButton);
    }

    function boot() {
        init();
        if (typeof MutationObserver !== 'undefined') {
            var pending = false;
            var observer = new MutationObserver(function () {
                if (pending) return;
                pending = true;
                requestAnimationFrame(function () {
                    pending = false;
                    init();
                });
            });
            observer.observe(document.documentElement, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.AGSitePortalNav = { init: init };
})();
