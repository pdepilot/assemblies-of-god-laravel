/**
 * Google Analytics 4 — loads only after analytics cookie consent.
 * Never sends email addresses or other PII in event parameters.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ag_ikenebgu_cookie_consent';
    var loaded = false;
    var blockedKeys = ['email', 'user_email', 'mail', 'phone', 'name'];

    function measurementId() {
        var meta = document.querySelector('meta[name="ag-google-analytics-id"]');
        return meta ? String(meta.getAttribute('content') || '').trim() : '';
    }

    function hasAnalyticsConsent() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var prefs = raw ? JSON.parse(raw) : null;
            return !!(prefs && prefs.analytics === true);
        } catch (e) {
            return false;
        }
    }

    function sanitizeParams(params) {
        var safe = {};
        if (!params || typeof params !== 'object') {
            return safe;
        }
        Object.keys(params).forEach(function (key) {
            if (blockedKeys.indexOf(String(key).toLowerCase()) !== -1) {
                return;
            }
            safe[key] = params[key];
        });
        return safe;
    }

    function loadGtag(id) {
        if (loaded || !id || document.querySelector('script[data-ag-ga4]')) {
            return;
        }
        loaded = true;

        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () {
            window.dataLayer.push(arguments);
        };

        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        script.setAttribute('data-ag-ga4', '1');
        script.onload = function () {
            window.gtag('js', new Date());
            window.gtag('config', id, {
                anonymize_ip: true,
                send_page_view: true
            });
        };
        document.head.appendChild(script);
    }

    function maybeInit() {
        var id = measurementId();
        if (!id || !hasAnalyticsConsent()) {
            return;
        }
        loadGtag(id);
    }

    window.AG_ANALYTICS = {
        event: function (name, params) {
            if (!hasAnalyticsConsent() || typeof window.gtag !== 'function') {
                return;
            }
            window.gtag('event', String(name || 'custom_event'), sanitizeParams(params));
        },
        hasConsent: hasAnalyticsConsent,
        measurementId: measurementId
    };

    maybeInit();
    window.addEventListener('ag:cookie-consent', function (e) {
        if (e.detail && e.detail.analytics) {
            maybeInit();
        }
    });
})();
