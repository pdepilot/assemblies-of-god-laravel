/**
 * SDTG donate shell — navbar / scroll-top only.
 * Online giving, donors, and live feed come from ../js/donate.js
 */
(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initNavbar() {
        const navbar = document.getElementById('navbar');
        const toggle = document.getElementById('navToggle');
        const menu = document.getElementById('navMenu');
        const backdrop = document.getElementById('navBackdrop');
        if (!navbar || !toggle || !menu) return;

        const closeMenu = () => {
            menu.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('no-scroll');
            if (backdrop) backdrop.classList.remove('active');
        };

        window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 40), { passive: true });
        navbar.classList.toggle('scrolled', window.scrollY > 40);
        toggle.addEventListener('click', () => {
            if (menu.classList.contains('open')) {
                closeMenu();
                return;
            }
            menu.classList.add('open');
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            if (backdrop) backdrop.classList.add('active');
            document.body.classList.add('no-scroll');
        });
        if (backdrop) backdrop.addEventListener('click', closeMenu);
        document.querySelectorAll('.nav-link').forEach((l) => l.addEventListener('click', closeMenu));
    }

    function initVideoLogos() {
        document.querySelectorAll('.logo-video__el').forEach((video) => {
            video.muted = true;
            video.playsInline = true;
            const play = () => video.play().catch(() => {});
            if (video.readyState >= 2) play();
            else video.addEventListener('loadeddata', play, { once: true });
        });
    }

    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;
        window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 400), { passive: true });
        btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' }));
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('is-loaded');
        initNavbar();
        initVideoLogos();
        initScrollTop();
    });
})();
