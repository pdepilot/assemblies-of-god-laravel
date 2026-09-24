(function () {
    "use strict";

    var root = document.getElementById("agPromoBanner");
    if (!root || document.documentElement.classList.contains("ag-promo-dismissed")) {
        return;
    }

    var opened = false;

    function openPromo() {
        if (opened || root.classList.contains("is-closed")) {
            return;
        }
        opened = true;
        root.classList.add("is-open");
        root.setAttribute("aria-hidden", "false");
        document.body.classList.add("ag-promo-open");
    }

    function closePromo() {
        root.classList.remove("is-open");
        root.classList.add("is-closed");
        root.setAttribute("aria-hidden", "true");
        document.body.classList.remove("ag-promo-open");
        document.documentElement.classList.add("ag-promo-dismissed");

        var key = root.getAttribute("data-dismiss-key");
        if (!key) {
            return;
        }

        try {
            window.sessionStorage.setItem(key, "1");
            if (root.getAttribute("data-show-every-visit") !== "1") {
                window.localStorage.setItem(key, "1");
            }
        } catch (e) { /* ignore */ }
    }

    function startWhenReady() {
        if (window.AG_PRELOADER_DONE || !document.getElementById("agPreloader")) {
            openPromo();
            return;
        }

        document.addEventListener("ag:preloader:done", openPromo, { once: true });
    }

    root.querySelectorAll("[data-ag-promo-close]").forEach(function (el) {
        el.addEventListener("click", closePromo);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && root.classList.contains("is-open")) {
            closePromo();
        }
    });

    startWhenReady();
})();
