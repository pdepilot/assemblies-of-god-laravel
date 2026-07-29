/**
 * Performance & Image SEO enhancements
 * - Lazy-load below-fold images
 * - decoding=async for images
 * - fetchpriority=high for hero/LCP images only
 */
(function () {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        optimizeImages();
        prefetchInternalLinks();
    }

    function optimizeImages() {
        var images = document.querySelectorAll('img:not([data-seo-processed])');
        var viewportH = window.innerHeight || 800;

        images.forEach(function (img, index) {
            img.setAttribute('data-seo-processed', 'true');

            if (!img.hasAttribute('decoding')) {
                img.setAttribute('decoding', 'async');
            }

            var rect = img.getBoundingClientRect();
            var isAboveFold = rect.top < viewportH * 1.1;
            var isHero = img.closest('.hero-slider, .hero-header, .hero, .ls-hero, .gallery-hero, .donate-hero, .reg-hero, .spk-hero, .contact-hero, .about-hero');

            if (isHero || isAboveFold || index < 2) {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'eager');
                }
                if (isHero && !img.hasAttribute('fetchpriority')) {
                    img.setAttribute('fetchpriority', 'high');
                }
            } else if (!img.hasAttribute('loading')) {
                img.setAttribute('loading', 'lazy');
            }

            if (!img.getAttribute('alt') || img.getAttribute('alt').trim() === '') {
                var src = img.getAttribute('src') || '';
                var name = src.split('/').pop().replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
                if (name) img.setAttribute('alt', name);
            }
        });
    }

    function prefetchInternalLinks() {
        if (!('IntersectionObserver' in window)) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var link = entry.target;
                if (link.dataset.prefetched) return;
                link.dataset.prefetched = 'true';
                var hint = document.createElement('link');
                hint.rel = 'prefetch';
                hint.href = link.href;
                document.head.appendChild(hint);
                observer.unobserve(link);
            });
        }, { rootMargin: '200px' });

        document.querySelectorAll('.seo-internal-nav a[href], .footer a[href], .navbar-nav a[href]').forEach(function (a) {
            if (a.hostname === location.hostname) observer.observe(a);
        });
    }

    (function loadSitePortalNav() {
        if (window.AGSitePortalNav || window.__agSitePortalNavLoading) {
            return;
        }
        if (document.querySelector('.btn-sdtg-nav, .btn--site-switch') === null) {
            return;
        }
        window.__agSitePortalNavLoading = true;
        var base = 'js/site-portal-nav.js';
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src') || '';
            if (src.indexOf('seo-performance.js') !== -1) {
                base = src.replace(/seo-performance\.js(\?.*)?$/, 'site-portal-nav.js$1');
                break;
            }
            if (src.indexOf('main.js') !== -1) {
                base = src.replace(/main\.js(\?.*)?$/, 'site-portal-nav.js$1');
                break;
            }
        }
        var el = document.createElement('script');
        el.src = base;
        el.defer = true;
        (document.body || document.documentElement).appendChild(el);
    })();
})();
