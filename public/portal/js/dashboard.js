(function () {
    'use strict';

    var API = '';
    var POLL_MS = 45000;
    var charts = {};
    var pollTimer = null;
    var pendingCharts = null;

    function handlerBase() {
        var configured = String(window.CMS_DASHBOARD_HANDLER_URL || '').trim();
        if (configured && !/AG_IKENEGBU_CHURCH_WEBSITE\/portal/i.test(configured)) {
            return configured;
        }

        var admin = String(window.CMS_ADMIN_BASE || '').replace(/\/?$/, '');
        if (admin && !/AG_IKENEGBU_CHURCH_WEBSITE\/portal/i.test(admin)) {
            return admin + '/handlers/dashboard-handler';
        }

        return (window.location.origin || '') + '/admin/handlers/dashboard-handler';
    }

    function apiUrl(query) {
        if (!API) API = handlerBase();
        return API + query;
    }

    function $(id) { return document.getElementById(id); }
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

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('en-US');
    }

    function setTrend(el, value) {
        if (!el) return;
        var num = Number(value || 0);
        el.hidden = false;
        el.classList.remove('cms-stat__trend--up', 'cms-stat__trend--down');
        if (num >= 0) {
            el.classList.add('cms-stat__trend--up');
            el.innerHTML = '<i class="fas fa-arrow-up" aria-hidden="true"></i> ' + Math.abs(num) + '%';
        } else {
            el.classList.add('cms-stat__trend--down');
            el.innerHTML = '<i class="fas fa-arrow-down" aria-hidden="true"></i> ' + Math.abs(num) + '%';
        }
    }

    function updateOverview(overview) {
        if (!overview) return;
        var ag = overview.ag || {};

        if ($('statTotalMembers')) $('statTotalMembers').textContent = formatNumber(ag.total_members);
        if ($('statTotalVisitors')) $('statTotalVisitors').textContent = formatNumber(ag.total_visitors);
        if ($('statWeeklyAttendance')) $('statWeeklyAttendance').textContent = formatNumber(ag.weekly_attendance);
        if ($('statReadyVisitors')) $('statReadyVisitors').textContent = formatNumber(ag.visitors_ready);
        if ($('statReadyBadge')) $('statReadyBadge').textContent = formatNumber(ag.visitors_ready) + ' Ready';
        if ($('statUpcomingBadge')) $('statUpcomingBadge').textContent = formatNumber(ag.upcoming_events) + ' Events';

        setTrend($('statMemberTrend'), ag.member_trend);
        setTrend($('statVisitorTrend'), ag.visitor_trend);

        var finance = overview.finance || {};
        if ($('statFinanceMonth') && finance.donations_month != null) {
            $('statFinanceMonth').textContent = '₦' + formatNumber(finance.donations_month);
        }
        if ($('statFinanceTrend') && finance.donation_trend != null) {
            var trend = Number(finance.donation_trend || 0);
            $('statFinanceTrend').textContent = (trend >= 0 ? '+' : '') + trend + '%';
        }

        var ministries = overview.ministries || {};
        if ($('statYouthMembers') && ministries.youths) {
            $('statYouthMembers').textContent = formatNumber(ministries.youths.members);
        }

        var ss = overview.sunday_school || {};
        if ($('statSsStudents') && ss.total_students != null) {
            $('statSsStudents').textContent = formatNumber(ss.total_students);
        }
        if ($('statSsTeachers') && ss.total_teachers != null) {
            $('statSsTeachers').textContent = formatNumber(ss.total_teachers);
        }
        if ($('statSsAttendance') && ss.attendance_today != null) {
            $('statSsAttendance').textContent = formatNumber(ss.attendance_today);
        }

        if ($('dashLastUpdated') && overview.updated_at) {
            var updated = new Date(overview.updated_at);
            $('dashLastUpdated').textContent = 'Updated ' + updated.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        }
    }

    function renderActivity(items) {
        var feed = $('dashActivityFeed');
        if (!feed) return;

        if (!items || !items.length) {
            feed.innerHTML = '<div class="cms-feed__item"><span class="cms-feed__dot cms-feed__dot--blue"></span><div><p class="cms-feed__text">No recent activity yet.</p></div></div>';
            return;
        }

        feed.innerHTML = items.map(function (item) {
            return '<div class="cms-feed__item">' +
                '<span class="cms-feed__dot cms-feed__dot--' + escapeHtml(item.icon || 'blue') + '" aria-hidden="true"></span>' +
                '<div><p class="cms-feed__text"><strong>' + escapeHtml(item.title) + '</strong> — ' + escapeHtml(item.text) + '</p>' +
                '<p class="cms-feed__time">' + escapeHtml(item.time) + '</p></div></div>';
        }).join('');
    }

    var chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#8b95a8', font: { family: 'Inter', size: 11 }, boxWidth: 12 }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: { color: '#5c6678', font: { size: 11 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: { color: '#5c6678', font: { size: 11 }, precision: 0 },
                beginAtZero: true
            }
        }
    };

    function upsertChart(id, config) {
        if (typeof Chart === 'undefined') return;
        var canvas = $(id);
        if (!canvas) return;
        if (charts[id]) {
            charts[id].data = config.data;
            charts[id].options = config.options || chartDefaults;
            charts[id].update();
            return;
        }
        charts[id] = new Chart(canvas, config);
    }

    function updateCharts(data) {
        if (!data) return;
        if (typeof Chart === 'undefined') {
            pendingCharts = data;
            return;
        }
        pendingCharts = null;
        var engagement = data.engagement || {};
        var registrations = data.registrations || {};
        var breakdown = data.breakdown || {};

        upsertChart('chartAttendance', {
            type: 'line',
            data: {
                labels: engagement.labels || [],
                datasets: [{
                    label: 'New Members',
                    data: engagement.members || [],
                    borderColor: 'rgba(251, 254, 6, 0.8)',
                    backgroundColor: 'rgba(251, 254, 6, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }, {
                    label: 'New Visitors',
                    data: engagement.visitors || [],
                    borderColor: 'rgba(59, 130, 246, 0.8)',
                    backgroundColor: 'rgba(59, 130, 246, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }, {
                    label: 'Sunday Attendance',
                    data: engagement.attendance || [],
                    borderColor: 'rgba(34, 197, 94, 0.85)',
                    backgroundColor: 'rgba(34, 197, 94, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }]
            },
            options: chartDefaults
        });

        upsertChart('chartRegistrations', {
            type: 'bar',
            data: {
                labels: registrations.labels || [],
                datasets: [{
                    label: 'Registrations',
                    data: registrations.values || [],
                    backgroundColor: 'rgba(139, 92, 246, 0.85)',
                    borderRadius: 6
                }]
            },
            options: chartDefaults
        });

        upsertChart('chartDonations', {
            type: 'doughnut',
            data: {
                labels: breakdown.labels || [],
                datasets: [{
                    data: breakdown.values || [],
                    backgroundColor: [
                        'rgba(251, 254, 6, 0.85)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(239, 68, 68, 0.75)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: chartDefaults.plugins.legend.labels } }
            }
        });
    }

    function applyPayload(data) {
        updateOverview(data.overview);
        updateCharts(data.charts);
        renderActivity(data.activity);
        if (window.CMS_DASHBOARD_FILTER && typeof CMS_DASHBOARD_FILTER.apply === 'function') {
            CMS_DASHBOARD_FILTER.apply();
        }
        if (window.CMSNotifications && CMSNotifications.sync) {
            CMSNotifications.sync(data);
        }
    }

    function fetchBootstrap() {
        return fetch(apiUrl('?action=bootstrap'), { credentials: 'same-origin' })            .then(function (res) {
                if (!res.headers.get('Content-Type') || res.headers.get('Content-Type').indexOf('application/json') === -1) {
                    throw new Error('Unable to load dashboard data.');
                }
                return res.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Unable to load dashboard.');
                applyPayload(data);
            })
            .catch(function (err) {
                toast(err.message || 'Dashboard failed to load.', 'info');
            });
    }

    function refreshLiveData() {
        Promise.all([
            fetch(apiUrl('?action=overview'), { credentials: 'same-origin' }).then(function (r) { return r.json(); }),
            fetch(apiUrl('?action=charts'), { credentials: 'same-origin' }).then(function (r) { return r.json(); }),
            fetch(apiUrl('?action=activity&limit=8'), { credentials: 'same-origin' }).then(function (r) { return r.json(); })
        ]).then(function (results) {            if (results[0].success) updateOverview(results[0].overview);
            if (results[1].success) updateCharts(results[1].charts);
            if (results[2].success) renderActivity(results[2].activity);
        }).catch(function () {});
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(refreshLiveData, POLL_MS);
    }

    function initWhenReady() {
        fetchBootstrap().then(startPolling);

        if (typeof Chart === 'undefined') {
            var chartWait = setInterval(function () {
                if (typeof Chart === 'undefined') return;
                clearInterval(chartWait);
                if (pendingCharts) updateCharts(pendingCharts);
            }, 50);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenReady);
    } else {
        initWhenReady();
    }
})();
