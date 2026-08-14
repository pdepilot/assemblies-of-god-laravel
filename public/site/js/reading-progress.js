/**
 * Reading progress bar + ensure back-to-top visibility on long content pages.
 */
(function () {
    'use strict';

    function initReadingProgress() {
        var bar = document.getElementById('readingProgress');
        if (!bar) {
            return;
        }
        var fill = bar.querySelector('span') || bar;
        var ticking = false;

        function update() {
            ticking = false;
            var doc = document.documentElement;
            var scrollTop = window.scrollY || doc.scrollTop || 0;
            var height = (doc.scrollHeight - doc.clientHeight) || 1;
            var pct = Math.min(100, Math.max(0, (scrollTop / height) * 100));
            fill.style.width = pct.toFixed(2) + '%';
            bar.classList.toggle('is-active', scrollTop > 40);
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });

        update();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReadingProgress);
    } else {
        initReadingProgress();
    }
})();
