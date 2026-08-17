/**
 * Load AdSense only after advertising (personalization) consent.
 * Never injects on pages that omit the AdSense meta / this script.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ag_ikenebgu_cookie_consent';
    var loaded = false;

    function clientId() {
        if (window.AG_ADSENSE && window.AG_ADSENSE.client) {
            return String(window.AG_ADSENSE.client);
        }
        var meta = document.querySelector('meta[name="google-adsense-account"]');
        if (!meta) {
            return '';
        }
        return meta.getAttribute('data-ag-adsense-client')
            || meta.getAttribute('content')
            || '';
    }

    function prefsAllowAds(prefs) {
        return !!(prefs && prefs.personalization);
    }

    function readStored() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function loadAdSense() {
        var client = clientId();
        if (loaded || !client || document.querySelector('script[data-ag-adsense]')) {
            return;
        }
        loaded = true;

        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(client);
        s.crossOrigin = 'anonymous';
        s.setAttribute('data-ag-adsense', '1');
        document.head.appendChild(s);
    }

    function maybeLoad(prefs) {
        if (prefsAllowAds(prefs)) {
            loadAdSense();
            return;
        }
        if (loaded || document.querySelector('script[data-ag-adsense]')) {
            window.location.reload();
        }
    }

    if (!clientId()) {
        return;
    }

    maybeLoad(readStored());
    window.addEventListener('ag:cookie-consent', function (e) {
        maybeLoad(e.detail || {});
    });
})();
