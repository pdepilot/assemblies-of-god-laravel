/**
 * First-party site traffic beacon.
 * Sends pageviews / heartbeats only when analytics cookie consent is granted.
 */
(function () {
    'use strict';

    var CONSENT_KEYS = ['ag_ikenebgu_cookie_consent'];
    var VISITOR_KEY = 'ag_site_visitor_key';
    var SESSION_KEY = 'ag_site_session_key';
    var HEARTBEAT_MS = 20000;

    var cfg = window.AG_SITE_TRAFFIC || {};
    var endpoint = cfg.endpoint || '';
    if (!endpoint) {
        return;
    }

    var pageviewKey = uuid();
    var enteredAt = Date.now();
    var lastSentDuration = 0;
    var started = false;
    var heartbeatTimer = null;

    function uuid() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function loadJson(key) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function hasAnalyticsConsent() {
        for (var i = 0; i < CONSENT_KEYS.length; i++) {
            var prefs = loadJson(CONSENT_KEYS[i]);
            if (prefs && prefs.analytics === true) {
                return true;
            }
        }
        return false;
    }

    function getOrCreate(storage, key) {
        try {
            var existing = storage.getItem(key);
            if (existing && /^[0-9a-f-]{36}$/i.test(existing)) {
                return existing;
            }
            var created = uuid();
            storage.setItem(key, created);
            return created;
        } catch (e) {
            return uuid();
        }
    }

    function detectSiteArea(path) {
        var p = (path || '').toLowerCase();
        if (p.indexOf('/sermon-library') !== -1) return 'sermon';
        if (p.indexOf('/register') !== -1) return 'register';
        return 'ag';
    }

    function deviceHints() {
        var w = Math.min(window.screen.width || 0, window.innerWidth || 0);
        var type = 'desktop';
        if (w && w < 768) type = 'mobile';
        else if (w && w < 1024) type = 'tablet';
        if (/Mobi|Android.*Mobile|iPhone|iPod/i.test(navigator.userAgent || '')) {
            type = 'mobile';
        } else if (/iPad|Tablet/i.test(navigator.userAgent || '')) {
            type = 'tablet';
        }
        return { type: type, width: window.innerWidth || 0, height: window.innerHeight || 0 };
    }

    function durationSeconds() {
        return Math.max(0, Math.floor((Date.now() - enteredAt) / 1000));
    }

    function payload(event, isExit) {
        var path = window.location.pathname || '/';
        return {
            event: event,
            visitor_key: getOrCreate(localStorage, VISITOR_KEY),
            session_key: getOrCreate(sessionStorage, SESSION_KEY),
            pageview_key: pageviewKey,
            path: path,
            title: document.title || '',
            referrer: document.referrer || '',
            site_area: detectSiteArea(path),
            duration_seconds: durationSeconds(),
            device: deviceHints(),
            is_exit: !!isExit
        };
    }

    function send(event, isExit, useBeacon) {
        if (!hasAnalyticsConsent()) {
            return;
        }
        var body = JSON.stringify(payload(event, isExit));
        lastSentDuration = durationSeconds();
        var crossOrigin = false;
        try {
            crossOrigin = new URL(endpoint, window.location.href).origin !== window.location.origin;
        } catch (e) { /* ignore */ }

        if (useBeacon && navigator.sendBeacon) {
            try {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(endpoint, blob)) {
                    return;
                }
            } catch (e) { /* fall through */ }
        }

        try {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body,
                credentials: crossOrigin ? 'omit' : 'same-origin',
                mode: crossOrigin ? 'cors' : 'same-origin',
                keepalive: !!isExit,
                cache: 'no-store'
            }).catch(function () {});
        } catch (e) { /* ignore */ }
    }

    function startTracking() {
        if (started || !hasAnalyticsConsent()) {
            return;
        }
        started = true;
        send('pageview', false, false);
        heartbeatTimer = window.setInterval(function () {
            if (document.visibilityState === 'hidden') {
                return;
            }
            var d = durationSeconds();
            if (d - lastSentDuration >= 15) {
                send('heartbeat', false, false);
            }
        }, HEARTBEAT_MS);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    }

    function onExit() {
        if (!started) {
            return;
        }
        stopHeartbeat();
        send('exit', true, true);
    }

    window.addEventListener('ag:cookie-consent', function (ev) {
        var detail = (ev && ev.detail) || {};
        if (detail.analytics) {
            startTracking();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (!started) return;
        if (document.visibilityState === 'hidden') {
            send('heartbeat', false, true);
        }
    });

    window.addEventListener('pagehide', onExit);
    window.addEventListener('beforeunload', function () {
        if (started) {
            send('exit', true, true);
        }
    });

    if (hasAnalyticsConsent()) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startTracking);
        } else {
            startTracking();
        }
    }
})();
