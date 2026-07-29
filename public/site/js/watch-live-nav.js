(function () {
    'use strict';

    function apiBase() {
        var path = window.location.pathname || '';
        return path.indexOf('/sermon-library') !== -1
            ? '../api/sermon-api.php'
            : 'api/sermon-api.php';
    }

    function libraryHref(hash) {
        var path = window.location.pathname || '';
        return path.indexOf('/sermon-library') !== -1
            ? './#' + hash
            : 'sermon-library/#' + hash;
    }

    function ensureNavLink(id, className, href, label) {
        var nav = document.querySelector('.navbar-nav');
        if (!nav) return null;

        var link = document.getElementById(id);
        if (!link) {
            link = document.createElement('a');
            link.id = id;
            link.className = className + ' is-hidden';
            link.href = href;
            link.innerHTML = '<span class="watch-live-dot" aria-hidden="true"></span> ' + label;
            var sermons = nav.querySelector('a[href*="sermon"]');
            if (sermons && sermons.parentNode) {
                sermons.parentNode.insertBefore(link, sermons.nextSibling);
            } else {
                nav.appendChild(link);
            }
        }
        return link;
    }

    function applyNav(data) {
        var watch = ensureNavLink('navWatchLive', 'nav-item nav-link watch-live-nav', libraryHref('watch-live'), 'Watch Live');
        var listen = ensureNavLink('navListenLive', 'nav-item nav-link listen-live-nav', libraryHref('listen-live'), 'Listen Live');
        if (watch) watch.classList.toggle('is-hidden', !data.show_watch_live_nav);
        if (listen) listen.classList.toggle('is-hidden', !data.show_listen_live_nav);
    }

    function poll() {
        fetch(apiBase() + '?action=bootstrap', { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    applyNav(data);
                }
            })
            .catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', poll);
    } else {
        poll();
    }

    if (typeof EventSource !== 'undefined') {
        try {
            var sse = new EventSource(apiBase() + '?action=stream');
            sse.addEventListener('live', function (ev) {
                try {
                    applyNav(JSON.parse(ev.data));
                } catch (e) {}
            });
        } catch (e) {}
    } else {
        setInterval(poll, 60000);
    }
})();
