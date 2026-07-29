(function () {
    "use strict";

    var body = document.body;
    var apiBase = (body.getAttribute("data-api-base") || "/api").replace(/\/$/, "");
    var token = body.getAttribute("data-portal-token") || "";
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";

    var form = document.getElementById("portalAccessForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            var status = document.getElementById("portalAccessStatus");
            fetch(apiBase + "/donor-portal-request.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ csrf_token: csrf, email: document.getElementById("portalEmail").value.trim() })
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (status) status.textContent = d.message || "Check your email.";
            }).catch(function () {
                if (status) status.textContent = "Unable to send link.";
            });
        });
    }

    document.querySelectorAll(".portal-action").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var fd = new FormData();
            fd.append("token", token);
            fd.append("action", btn.getAttribute("data-action"));
            fd.append("id", btn.getAttribute("data-id"));
            fetch(apiBase + "/donor-portal-manage.php", { method: "POST", body: fd })
                .then(function (r) { return r.json(); })
                .then(function () { window.location.reload(); });
        });
    });

    function requestStatement(sendEmail) {
        var status = document.getElementById("statementStatus");
        var yearEl = document.getElementById("statementYear");
        if (!yearEl || !token) return;
        fetch(apiBase + "/generate-statement.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ portal_token: token, year: Number(yearEl.value), send_email: !!sendEmail })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (status) status.textContent = d.message || "Statement ready.";
            if (d.download) window.open(d.download, "_blank");
        }).catch(function () {
            if (status) status.textContent = "Unable to generate statement.";
        });
    }

    var statementForm = document.getElementById("statementForm");
    if (statementForm) {
        statementForm.addEventListener("submit", function (e) {
            e.preventDefault();
            requestStatement(false);
        });
    }
    var emailBtn = document.getElementById("emailStatementBtn");
    if (emailBtn) {
        emailBtn.addEventListener("click", function () { requestStatement(true); });
    }
})();
