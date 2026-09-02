/**
 * Blog UX — search suggestions + article share (jenfrankfarms.com/blog pattern).
 */
(function () {
    "use strict";

    function initSearchSuggest() {
        var input = document.getElementById("blogSearchInput");
        var list = document.getElementById("blogSuggestList");
        if (!input || !list) return;

        var timer = null;
        var controller = null;

        function hide() {
            list.hidden = true;
            list.innerHTML = "";
            input.setAttribute("aria-expanded", "false");
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }

        function show(items) {
            if (!items.length) {
                hide();
                return;
            }

            list.innerHTML = items.map(function (item) {
                return (
                    '<li role="option">' +
                        '<a href="' + escapeHtml(item.url) + '">' +
                            '<strong>' + escapeHtml(item.title) + '</strong>' +
                            '<span>' + escapeHtml(String(item.reading_time_minutes || 1)) + ' min read</span>' +
                        '</a>' +
                    '</li>'
                );
            }).join("");

            list.hidden = false;
            input.setAttribute("aria-expanded", "true");
        }

        input.addEventListener("input", function () {
            var query = input.value.trim();
            clearTimeout(timer);

            if (query.length < 2) {
                hide();
                return;
            }

            timer = setTimeout(function () {
                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();

                fetch(window.location.origin + "/api/blog/suggest?q=" + encodeURIComponent(query), {
                    headers: { Accept: "application/json" },
                    signal: controller.signal
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) { show(data.suggestions || []); })
                    .catch(function () { /* ignore abort/network */ });
            }, 220);
        });

        document.addEventListener("click", function (event) {
            if (!event.target.closest(".blog-search-wrap")) {
                hide();
            }
        });
    }

    function initCopyLink() {
        var btn = document.getElementById("copyArticleLink");
        if (!btn || !navigator.clipboard) return;

        btn.addEventListener("click", function () {
            var shareUrl = btn.getAttribute("data-share-url");
            if (!shareUrl) return;

            navigator.clipboard.writeText(shareUrl).then(function () {
                btn.classList.add("copied");
                setTimeout(function () {
                    btn.classList.remove("copied");
                }, 1500);
            }).catch(function () { /* ignore */ });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initSearchSuggest();
        initCopyLink();
    });
})();
