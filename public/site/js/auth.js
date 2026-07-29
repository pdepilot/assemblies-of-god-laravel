/**
 * AG Ikenebgu & SDTG — Enterprise Admin Login
 */
(function () {
    'use strict';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var WELCOME_MSGS = [
        'Manage Your Church With Excellence',
        'Lead With Insight',
        'Track Growth In Real Time',
        'Powering AG IKENEGBU & SDTG'
    ];

    var HIGHLIGHTS = [
        'Unified dashboard for church & crusade operations',
        'Real-time attendance and registration analytics',
        'Enterprise-grade role-based access control',
        'SEO, livestream, and donation management in one place'
    ];

    var TOASTS = [
        { icon: 'fa-user-plus', type: '', text: '47 New Registrations Today' },
        { icon: 'fa-search', type: 'seo', text: 'SEO Audit Completed' },
        { icon: 'fa-chart-line', type: 'gold', text: 'Attendance Report Generated' },
        { icon: 'fa-hand-holding-heart', type: 'gold', text: '₦2.4M Donations Tracked This Month' },
        { icon: 'fa-broadcast-tower', type: '', text: 'SDTG Livestream Control Ready' }
    ];

    var METRICS = [
        { el: 'metricMembers', target: 2847, suffix: '' },
        { el: 'metricAttendance', target: 94, suffix: '%' },
        { el: 'metricRegistrations', target: 1240, suffix: '' },
        { el: 'metricDonations', target: 48, suffix: 'M', prefix: '₦' },
        { el: 'metricEvents', target: 36, suffix: '' }
    ];

    /* ---- 3D logo videos ---- */
    function initLogoVideos() {
        document.querySelectorAll('.auth-logo-video__el').forEach(function (video) {
            video.muted = true;
            video.playsInline = true;
            var play = function () {
                video.play().catch(function () {});
            };
            if (video.readyState >= 2) {
                play();
            } else {
                video.addEventListener('loadeddata', play, { once: true });
            }
        });
    }

    /* ---- Particles ---- */
    function initParticles() {
        var canvas = document.getElementById('authParticles');
        if (!canvas || reducedMotion) return;

        var ctx = canvas.getContext('2d');
        var particles = [];
        var animId;

        function resize() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            var count = Math.min(80, Math.floor((canvas.width * canvas.height) / 12000));
            particles = Array.from({ length: count }, function () {
                return {
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.8 + 0.4,
                    dx: (Math.random() - 0.5) * 0.35,
                    dy: (Math.random() - 0.5) * 0.35,
                    alpha: Math.random() * 0.5 + 0.15
                };
            });
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(function (p) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(251, 254, 6, ' + p.alpha + ')';
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            });
            animId = requestAnimationFrame(draw);
        }

        resize();
        draw();
        window.addEventListener('resize', resize);
    }

    /* ---- Rotating text ---- */
    function initRotator(containerSelector, itemClass, messages, interval) {
        var container = document.querySelector(containerSelector);
        if (!container) return;

        container.innerHTML = messages.map(function (msg, i) {
            return '<span class="' + itemClass + (i === 0 ? ' is-active' : '') + '" role="status">' + msg + '</span>';
        }).join('');

        var items = container.querySelectorAll('.' + itemClass);
        if (items.length < 2) return;

        var idx = 0;
        setInterval(function () {
            items[idx].classList.remove('is-active');
            idx = (idx + 1) % items.length;
            items[idx].classList.add('is-active');
        }, interval || 4000);
    }

    /* ---- Animated counters ---- */
    function animateCounter(el, target, prefix, suffix, duration) {
        if (!el) return;
        var start = 0;
        var startTime = null;
        prefix = prefix || '';
        suffix = suffix || '';

        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(start + (target - start) * eased);
            el.textContent = prefix + current.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    }

    function initMetrics() {
        METRICS.forEach(function (m, i) {
            setTimeout(function () {
                var el = document.getElementById(m.el);
                animateCounter(el, m.target, m.prefix, m.suffix, 1800);
            }, 600 + i * 150);
        });
    }

    /* ---- Stagger chips ---- */
    function initChips() {
        document.querySelectorAll('.auth-chip').forEach(function (chip, i) {
            chip.style.animationDelay = (i * 0.04) + 's';
        });
    }

    /* ---- Toasts ---- */
    function showToast(data) {
        var container = document.getElementById('authToasts');
        if (!container) return;

        var iconClass = data.type ? ' auth-toast__icon--' + data.type : '';
        var toast = document.createElement('div');
        toast.className = 'auth-toast';
        toast.setAttribute('role', 'status');
        toast.innerHTML =
            '<span class="auth-toast__icon' + iconClass + '" aria-hidden="true"><i class="fas ' + data.icon + '"></i></span>' +
            '<span>' + data.text + '</span>';

        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('is-leaving');
            setTimeout(function () { toast.remove(); }, 400);
        }, 4500);
    }

    function initToastLoop() {
        if (reducedMotion) return;
        var idx = 0;
        setTimeout(function () { showToast(TOASTS[0]); }, 3000);
        setInterval(function () {
            idx = (idx + 1) % TOASTS.length;
            showToast(TOASTS[idx]);
        }, 12000);
    }

    /* ---- Form ---- */
    function initForm() {
        var form = document.getElementById('authForm');
        var email = document.getElementById('authEmail');
        var password = document.getElementById('authPassword');
        var toggle = document.getElementById('authPasswordToggle');
        var submit = document.getElementById('authSubmit');
        var overlay = document.getElementById('authOverlay');
        var progressBar = document.getElementById('authProgressBar');
        var overlayStatus = document.getElementById('authOverlayStatus');

        if (!form) return;

        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var isPassword = password.type === 'password';
                password.type = isPassword ? 'text' : 'password';
                toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                toggle.innerHTML = '<i class="fas fa-eye' + (isPassword ? '-slash' : '') + '" aria-hidden="true"></i>';
            });
        }

        if (submit) {
            submit.addEventListener('click', function (e) {
                var rect = submit.getBoundingClientRect();
                var ripple = document.createElement('span');
                ripple.className = 'auth-submit__ripple';
                ripple.style.left = (e.clientX - rect.left) + 'px';
                ripple.style.top = (e.clientY - rect.top) + 'px';
                ripple.style.width = ripple.style.height = '20px';
                ripple.style.marginLeft = ripple.style.marginTop = '-10px';
                submit.appendChild(ripple);
                setTimeout(function () { ripple.remove(); }, 600);
            });
        }

        function validate() {
            var valid = true;
            var emailField = email.closest('.auth-field');
            var passField = password.closest('.auth-field');

            emailField.classList.remove('is-error');
            passField.classList.remove('is-error');
            email.classList.remove('is-invalid');
            password.classList.remove('is-invalid');

            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                emailField.classList.add('is-error');
                email.classList.add('is-invalid');
                email.setAttribute('aria-invalid', 'true');
                valid = false;
            } else {
                email.setAttribute('aria-invalid', 'false');
            }

            if (!password.value || password.value.length < 6) {
                passField.classList.add('is-error');
                password.classList.add('is-invalid');
                password.setAttribute('aria-invalid', 'true');
                valid = false;
            } else {
                password.setAttribute('aria-invalid', 'false');
            }

            return valid;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validate()) return;

            submit.disabled = true;
            submit.classList.add('is-loading');
            document.body.classList.add('no-scroll');
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');

            var steps = [
                { pct: 25, text: 'Verifying credentials…' },
                { pct: 50, text: 'Establishing secure session…' },
                { pct: 75, text: 'Loading role permissions…' },
                { pct: 100, text: 'Authentication complete' }
            ];

            var step = 0;
            var interval = setInterval(function () {
                if (step >= steps.length) {
                    clearInterval(interval);
                    setTimeout(function () {
                        overlay.classList.remove('is-active');
                        overlay.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('no-scroll');
                        submit.disabled = false;
                        submit.classList.remove('is-loading');
                        progressBar.style.width = '0';
                        overlayStatus.textContent = 'Verifying credentials…';
                        window.location.href = 'admin-dashboard';
                    }, 600);
                    return;
                }
                progressBar.style.width = steps[step].pct + '%';
                overlayStatus.textContent = steps[step].text;
                step++;
            }, 700);
        });
    }

    /* ---- Init ---- */
    function init() {
        initLogoVideos();
        initParticles();
        initRotator('.auth-welcome__rotate', 'auth-welcome__msg', WELCOME_MSGS, 3800);
        initRotator('.auth-highlight', 'auth-highlight__item', HIGHLIGHTS, 4500);
        initMetrics();
        initChips();
        initToastLoop();
        initForm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
