(function () {
    'use strict';

    var API_BASE = '';
    var POLL_MS = 8000;
    var POLL_HIDDEN_MS = 30000;
    var pollTimer = null;
    var audioCtx = null;
    var state = {
        csrf: '',
        latestId: 0,
        lastReadId: 0,
        unreadCount: 0,
        ready: false
    };

    function handlerBase() {
        var configured = String(window.CMS_DASHBOARD_HANDLER_URL || '').trim();
        if (configured && !/AG_IKENEGBU_CHURCH_WEBSITE\/portal/i.test(configured)) {
            return configured.replace(/\/?$/, '');
        }

        var admin = String(window.CMS_ADMIN_BASE || '').replace(/\/?$/, '');
        if (admin && !/AG_IKENEGBU_CHURCH_WEBSITE\/portal/i.test(admin)) {
            return admin + '/handlers/dashboard-handler';
        }

        return (window.location.origin || '') + '/admin/handlers/dashboard-handler';
    }

    function getCsrfToken() {
        if (state.csrf) return state.csrf;
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function escapeHtml(value) {
        if (value == null) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function toast(message, type) {
        if (window.CMS && CMS.showToast) {
            CMS.showToast(message, type || 'info');
        }
    }

    function updateBadge(count) {
        state.unreadCount = count;
        var dot = document.querySelector('#cmsNotifyBtn .cms-dot');
        if (dot) {
            dot.style.display = count > 0 ? '' : 'none';
        }
        var btn = document.getElementById('cmsNotifyBtn');
        if (btn) {
            btn.setAttribute('aria-label', count > 0 ? ('Notifications, ' + count + ' unread') : 'Notifications');
        }
    }

    function renderNotifications(items) {
        var list = document.getElementById('cmsNotifyList');
        if (!list) return;

        if (!items || !items.length) {
            list.innerHTML = '<div class="cms-notify-empty">No notifications yet. Activity from Members and Visitors will appear here.</div>';
            return;
        }

        list.innerHTML = items.map(function (item) {
            var unreadClass = item.is_unread ? ' is-unread' : '';
            return '<div class="cms-notify-item' + unreadClass + '" data-id="' + escapeHtml(item.id) + '">' +
                '<div class="cms-notify-item__icon cms-notify-item__icon--' + escapeHtml(item.icon || 'gold') + '">' +
                '<i class="fas fa-bell" aria-hidden="true"></i></div>' +
                '<div class="cms-notify-item__text"><strong>' + escapeHtml(item.title) + '</strong>' +
                '<span>' + escapeHtml(item.text) + '</span>' +
                '<div class="cms-notify-item__time">' + escapeHtml(item.time) + '</div></div></div>';
        }).join('');
    }

    function applyNotificationPayload(data, options) {
        options = options || {};
        if (data.csrf_token) state.csrf = data.csrf_token;
        if (typeof data.last_read_id === 'number') state.lastReadId = data.last_read_id;
        if (typeof data.latest_id === 'number' && data.latest_id >= state.latestId) {
            state.latestId = data.latest_id;
        }
        if (!options.incremental && Array.isArray(data.notifications)) {
            renderNotifications(data.notifications);
        }
        if (typeof data.unread_count === 'number') updateBadge(data.unread_count);
    }

    function requestNotifications(sinceId) {
        var url = API_BASE + '?action=notifications';
        if (sinceId) url += '&since_id=' + encodeURIComponent(String(sinceId));

        return fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) {
                if (res.status === 401 || res.status === 419) return null;
                var type = res.headers.get('Content-Type') || '';
                if (type.indexOf('application/json') === -1) return null;
                return res.json();
            })
            .catch(function () { return null; });
    }

    function fetchNotifications() {
        return requestNotifications(null).then(function (data) {
            if (!data || !data.success) return;
            applyNotificationPayload(data);
            state.ready = true;
        });
    }

    function toastNewItems(items) {
        if (!items || !items.length) return;
        playAdminAlert(items);
        // Newest first from API — toast oldest-first so order feels natural.
        items.slice().reverse().forEach(function (item) {
            var title = item.title || 'New notification';
            var text = item.text || '';
            toast(text ? (title + ': ' + text) : title, 'info');
        });
    }

    function getAudioContext() {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) {
            return null;
        }
        if (!audioCtx) {
            audioCtx = new AC();
        }
        return audioCtx;
    }

    function unlockAlertAudio() {
        var ctx = getAudioContext();
        if (!ctx) {
            return;
        }
        if (ctx.state === 'suspended') {
            ctx.resume().catch(function () {});
        }
    }

    function startTone(ctx, dest, start, freq, dur) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(freq, start);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.85, start + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + dur);
        osc.connect(gain);
        gain.connect(dest);
        osc.start(start);
        osc.stop(start + dur + 0.03);
    }

    function playAdminAlert(items) {
        var ctx = getAudioContext();
        if (!ctx) {
            return;
        }

        var urgent = !items || items.some(function (item) {
            return item && (item.play_alert || item.severity === 'warning' || item.severity === 'critical');
        });

        var run = function () {
            var now = ctx.currentTime;
            var master = ctx.createGain();
            master.gain.setValueAtTime(0.0001, now);
            master.gain.exponentialRampToValueAtTime(urgent ? 0.32 : 0.22, now + 0.02);
            master.connect(ctx.destination);

            var bursts = urgent ? [0, 0.4, 0.8] : [0, 0.45];
            bursts.forEach(function (offset) {
                startTone(ctx, master, now + offset, urgent ? 940 : 780, 0.15);
                startTone(ctx, master, now + offset + 0.17, urgent ? 560 : 520, 0.18);
            });

            var fadeAt = now + (urgent ? 1.2 : 0.85);
            master.gain.setValueAtTime(urgent ? 0.32 : 0.22, fadeAt);
            master.gain.exponentialRampToValueAtTime(0.0001, fadeAt + 0.18);
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(run).catch(function () {});
            return;
        }
        run();
    }

    function pollForNew() {
        if (!state.ready || !state.latestId) {
            return fetchNotifications();
        }

        var knownLatest = state.latestId;
        return requestNotifications(knownLatest).then(function (data) {
            if (!data || !data.success) return;
            var fresh = Array.isArray(data.notifications) ? data.notifications : [];
            if (fresh.length) {
                toastNewItems(fresh);
                return fetchNotifications();
            }
            if (typeof data.unread_count === 'number') updateBadge(data.unread_count);
            if (typeof data.latest_id === 'number' && data.latest_id > state.latestId) {
                state.latestId = data.latest_id;
            }
        });
    }

    function currentPollMs() {
        return document.hidden ? POLL_HIDDEN_MS : POLL_MS;
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollForNew, currentPollMs());
    }

    function restartPolling() {
        startPolling();
        if (!document.hidden) pollForNew();
    }

    function markAllRead() {
        var targetId = state.latestId || state.lastReadId;
        if (!targetId) {
            updateBadge(0);
            return Promise.resolve();
        }

        var fd = new FormData();
        fd.append('action', 'mark_notifications_read');
        fd.append('csrf_token', getCsrfToken());
        fd.append('last_log_id', String(targetId));

        return fetch(API_BASE, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Unable to mark notifications read.');
                state.lastReadId = data.last_read_id || targetId;
                updateBadge(data.unread_count || 0);
                document.querySelectorAll('.cms-notify-item.is-unread').forEach(function (el) {
                    el.classList.remove('is-unread');
                });
                toast('All notifications marked as read.', 'success');
            })
            .catch(function (err) {
                toast(err.message || 'Unable to update notifications.', 'info');
            });
    }

    function bindMarkRead() {
        var btn = document.getElementById('cmsMarkRead');
        if (!btn || btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
            markAllRead();
        });
    }

    function init() {
        API_BASE = handlerBase();
        bindMarkRead();
        fetchNotifications().then(startPolling);
        document.addEventListener('pointerdown', unlockAlertAudio, { once: true, capture: true });
        document.addEventListener('keydown', unlockAlertAudio, { once: true, capture: true });
        document.addEventListener('visibilitychange', restartPolling);
        window.addEventListener('focus', function () {
            if (!document.hidden) pollForNew();
        });
    }

    window.CMSNotifications = {
        sync: applyNotificationPayload,
        refresh: fetchNotifications,
        poll: pollForNew,
        markAllRead: markAllRead
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
