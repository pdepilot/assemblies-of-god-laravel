/**
 * Legacy login visuals for Laravel (particles, rotator, password toggle).
 * Form submits normally to POST /login.
 */
(function () {
    'use strict';

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initLogoVideos() {
        document.querySelectorAll('.auth-logo-video__el').forEach(function (video) {
            video.muted = true;
            video.playsInline = true;
            var play = function () { video.play().catch(function () {}); };
            if (video.readyState >= 2) play();
            else video.addEventListener('loadeddata', play, { once: true });
        });
    }

    function initParticles() {
        var canvas = document.getElementById('authParticles');
        if (!canvas || reducedMotion) return;
        var ctx = canvas.getContext('2d');
        var particles = [];
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
                p.x += p.dx; p.y += p.dy;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            });
            requestAnimationFrame(draw);
        }
        resize(); draw();
        window.addEventListener('resize', resize);
    }

    function initRotator(containerSelector, itemClass, messages, interval) {
        var container = document.querySelector(containerSelector);
        if (!container) return;
        container.innerHTML = messages.map(function (msg, i) {
            return '<span class="' + itemClass + (i === 0 ? ' is-active' : '') + '">' + msg + '</span>';
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

    function initPasswordToggle() {
        var password = document.getElementById('authPassword');
        var toggle = document.getElementById('authPasswordToggle');
        if (!toggle || !password) return;
        toggle.addEventListener('click', function () {
            var isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            toggle.innerHTML = '<i class="fas fa-eye' + (isPassword ? '-slash' : '') + '" aria-hidden="true"></i>';
        });
    }

    function initSubmitOverlay() {
        var form = document.getElementById('authForm');
        var overlay = document.getElementById('authOverlay');
        var progressBar = document.getElementById('authProgressBar');
        if (!form || !overlay) return;
        form.addEventListener('submit', function () {
            document.body.classList.add('no-scroll');
            overlay.classList.add('is-active');
            overlay.setAttribute('aria-hidden', 'false');
            if (progressBar) progressBar.style.width = '70%';
        });
    }

    function init() {
        initLogoVideos();
        initParticles();
        initRotator('.auth-welcome__rotate', 'auth-welcome__msg', [
            'Manage Your Church With Excellence',
            'Lead With Insight',
            'Track Growth In Real Time',
            'Powering AGC IKENEGBU & SDTG'
        ], 3800);
        initPasswordToggle();
        initSubmitOverlay();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
