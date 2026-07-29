/**
 * SDTG Cookie Consent — Premium preference management
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'sdtg_cookie_consent';
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const DEFAULT_PREFS = {
        essential: true,
        analytics: false,
        performance: false,
        personalization: false,
        timestamp: null,
        version: 1
    };

    function loadPrefs() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    function savePrefs(prefs) {
        prefs.timestamp = Date.now();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        window.dispatchEvent(new CustomEvent('sdtg:cookie-consent', { detail: prefs }));
    }

    function ripple(e, btn) {
        if (prefersReducedMotion) return;
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const rippleEl = document.createElement('span');
        rippleEl.className = 'cookie-consent__ripple';
        rippleEl.style.width = rippleEl.style.height = `${size}px`;
        rippleEl.style.left = `${e.clientX - rect.left - size / 2}px`;
        rippleEl.style.top = `${e.clientY - rect.top - size / 2}px`;
        btn.appendChild(rippleEl);
        setTimeout(() => rippleEl.remove(), 600);
    }

    function initParticles(canvas) {
        if (!canvas || prefersReducedMotion) return;
        const ctx = canvas.getContext('2d');
        let particles = [];

        const resize = () => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            particles = Array.from({ length: 12 }, () => ({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 1.5 + 0.5,
                dx: (Math.random() - 0.5) * 0.2,
                dy: (Math.random() - 0.5) * 0.2,
                alpha: Math.random() * 0.3 + 0.1
            }));
        };

        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(251, 254, 6, ${p.alpha})`;
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            });
            requestAnimationFrame(draw);
        };

        resize();
        draw();
        window.addEventListener('resize', resize);
    }

    function hideBanner(banner) {
        if (!banner?.parentNode) return;
        banner.classList.remove('is-visible');
        banner.classList.add('is-hidden');
        setTimeout(() => banner.remove(), 650);
    }

    function syncToggles(prefs) {
        document.querySelectorAll('[data-cookie-pref]').forEach(input => {
            const key = input.dataset.cookiePref;
            if (key === 'essential') {
                input.checked = true;
                input.disabled = true;
            } else {
                input.checked = !!prefs[key];
                input.disabled = false;
            }
        });
    }

    function openModal(modal, banner) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        modal.querySelector('.cookie-modal__close')?.focus();
        banner?.classList.add('is-hidden');
    }

    function closeModal(modal, banner) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
        if (banner?.parentNode && !loadPrefs()) banner.classList.remove('is-hidden');
    }

    function trapFocus(modal, e) {
        if (e.key !== 'Tab' || !modal.classList.contains('is-open')) return;
        const focusable = modal.querySelectorAll('button, [href], input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function init() {
        const banner = document.getElementById('cookieConsent');
        const modal = document.getElementById('cookieModal');
        const existing = loadPrefs();

        if (banner) {
            initParticles(banner.querySelector('.cookie-consent__particles'));
            if (existing) {
                banner.remove();
            } else {
                setTimeout(() => {
                    banner.classList.add('is-visible', 'is-ready');
                    banner.setAttribute('aria-hidden', 'false');
                }, prefersReducedMotion ? 100 : 1200);
            }
        }

        const acceptAll = () => {
            savePrefs({ ...DEFAULT_PREFS, analytics: true, performance: true, personalization: true });
            hideBanner(banner);
            if (modal) closeModal(modal, banner);
        };

        const essentialOnly = () => {
            savePrefs({ ...DEFAULT_PREFS });
            hideBanner(banner);
            if (modal) closeModal(modal, banner);
        };

        banner?.querySelector('[data-action="accept-all"]')?.addEventListener('click', e => {
            ripple(e, e.currentTarget);
            setTimeout(acceptAll, 200);
        });

        banner?.querySelector('[data-action="essential-only"]')?.addEventListener('click', e => {
            ripple(e, e.currentTarget);
            setTimeout(essentialOnly, 200);
        });

        banner?.querySelector('[data-action="customize"]')?.addEventListener('click', e => {
            ripple(e, e.currentTarget);
            if (modal) {
                syncToggles(loadPrefs() || DEFAULT_PREFS);
                openModal(modal, banner);
            }
        });

        document.querySelectorAll('[data-action="open-cookie-settings"]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                if (!modal) return;
                syncToggles(loadPrefs() || DEFAULT_PREFS);
                openModal(modal, banner);
            });
        });

        if (modal) {
            modal.querySelector('[data-action="save-preferences"]')?.addEventListener('click', () => {
                const prefs = { ...DEFAULT_PREFS };
                document.querySelectorAll('[data-cookie-pref]').forEach(input => {
                    if (input.dataset.cookiePref !== 'essential') {
                        prefs[input.dataset.cookiePref] = input.checked;
                    }
                });
                savePrefs(prefs);
                hideBanner(banner);
                closeModal(modal, banner);
            });

            modal.querySelector('[data-action="accept-all-modal"]')?.addEventListener('click', acceptAll);

            modal.querySelector('.cookie-modal__close')?.addEventListener('click', () => closeModal(modal, banner));

            modal.addEventListener('click', e => {
                if (e.target === modal) closeModal(modal, banner);
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(modal, banner);
                trapFocus(modal, e);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', init);
    window.SDTG_COOKIE = { loadPrefs, savePrefs, STORAGE_KEY };
})();
