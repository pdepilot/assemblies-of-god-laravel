(function () {
    'use strict';

    if (!document.body || !document.body.classList.contains('cms-app')) {
        return;
    }

    var sessionCfg = window.CMS_SESSION || {};
    var API = sessionCfg.apiUrl || ((window.CMS_ADMIN_BASE || '/admin').replace(/\/?$/, '/') + 'handlers/session-handler');
    var IDLE_MS = Number(sessionCfg.lifetimeMs) || (30 * 60 * 1000);
    var WARNING_MS = Number(sessionCfg.warningMs) || (25 * 60 * 1000);
    var lastActivity = Date.now();
    var warningShown = false;
    var modal = null;
    var countdownEl = null;
    var countdownTimer = null;
    var extendTimer = null;
    var loginUrl = sessionCfg.loginUrl || ((window.CMS_ADMIN_BASE || '/admin').replace(/\/?$/, '/') + 'login?reason=inactivity');

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function hideWarning() {
        warningShown = false;
        if (countdownTimer) clearInterval(countdownTimer);
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    function resetTimer() {
        lastActivity = Date.now();
        if (warningShown) {
            hideWarning();
        }
        if (extendTimer) clearTimeout(extendTimer);
        extendTimer = setTimeout(extendSession, 1500);
    }

    function bindActivity() {
        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
            document.addEventListener(evt, resetTimer, { passive: true });
        });
    }

    function ensureModal() {
        if (modal) return;
        modal = document.createElement('div');
        modal.className = 'cms-modal';
        modal.id = 'sessionTimeoutModal';
        modal.setAttribute('role', 'alertdialog');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML =
            '<div class="cms-modal__backdrop"></div>' +
            '<div class="cms-modal__dialog cms-modal__dialog--sm">' +
            '<div class="cms-modal__head"><h2 class="cms-modal__title">Session Expiring</h2></div>' +
            '<div class="cms-modal__body">' +
            '<p>Your session will expire soon due to inactivity. Time remaining: <strong id="sessionCountdown">5:00</strong>.</p>' +
            '<p style="color:var(--cms-text-muted);font-size:0.85rem;margin-top:8px">Move your mouse or click Stay Signed In to continue working.</p>' +
            '</div>' +
            '<div class="cms-modal__foot">' +
            '<button type="button" class="cms-btn cms-btn--primary" id="sessionStayBtn">Stay Signed In</button>' +
            '</div></div>';
        document.body.appendChild(modal);
        countdownEl = document.getElementById('sessionCountdown');
        document.getElementById('sessionStayBtn').addEventListener('click', function () {
            extendSession();
            resetTimer();
        });
    }

    function showWarning() {
        warningShown = true;
        ensureModal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        var expiresAt = lastActivity + IDLE_MS;
        if (countdownTimer) clearInterval(countdownTimer);
        countdownTimer = setInterval(function () {
            var left = Math.max(0, expiresAt - Date.now());
            var mins = Math.floor(left / 60000);
            var secs = Math.floor((left % 60000) / 1000);
            if (countdownEl) countdownEl.textContent = mins + ':' + String(secs).padStart(2, '0');
            if (left <= 0) {
                clearInterval(countdownTimer);
                forceLogout();
            }
        }, 1000);
    }

    function postAction(action) {
        var token = csrfToken();
        if (!token) {
            return Promise.reject(new Error('Missing CSRF token'));
        }
        var fd = new FormData();
        fd.append('action', action);
        fd.append('_token', token);
        return fetch(API, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (r) { return r.json(); });
    }

    function extendSession() {
        postAction('extend')
            .then(function (data) {
                if (!data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(function () { /* ignore */ });
    }

    function forceLogout() {
        hideWarning();
        postAction('timeout_logout')
            .then(function (data) {
                window.location.href = (data && data.redirect) || loginUrl;
            })
            .catch(function () {
                window.location.href = loginUrl;
            });
    }

    function syncWithServer() {
        fetch(API + (API.indexOf('?') >= 0 ? '&' : '?') + 'action=ping', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success || !data.authenticated) {
                    forceLogout();
                    return;
                }
                if (typeof data.remaining === 'number' && data.remaining <= 0) {
                    forceLogout();
                }
            })
            .catch(function () { /* ignore transient network errors */ });
    }

    function tick() {
        var idle = Date.now() - lastActivity;
        if (idle >= IDLE_MS) {
            forceLogout();
            return;
        }
        if (idle >= WARNING_MS && !warningShown) {
            showWarning();
        }
    }

    bindActivity();
    setInterval(tick, 1000);
    setInterval(syncWithServer, 60000);
    resetTimer();
})();
