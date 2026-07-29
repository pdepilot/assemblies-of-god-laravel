/**
 * AG Ikenebgu — giving story modal (donate page)
 */
(function () {
    "use strict";

    var STORAGE_KEY = "agGivingStories";
    var MAX_STORY_LEN = 1200;

    var CATEGORIES = [
        { id: "offering", label: "Offering", icon: "fa-hand-holding-heart" },
        { id: "tithes", label: "Tithes", icon: "fa-percent" },
        { id: "charity", label: "Charity", icon: "fa-heart" },
        { id: "building", label: "Building Fund", icon: "fa-church" }
    ];

    var MODAL_HTML =
        '<div class="modal fade ag-giving-modal" id="agGivingStoryModal" tabindex="-1" aria-labelledby="agGivingStoryModalLabel" aria-hidden="true">' +
        '  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">' +
        '    <div class="modal-content ag-giving-modal__content">' +
        '      <div class="ag-giving-modal__glow ag-giving-modal__glow--1" aria-hidden="true"></div>' +
        '      <div class="ag-giving-modal__glow ag-giving-modal__glow--2" aria-hidden="true"></div>' +
        '      <div class="modal-header ag-giving-modal__header">' +
        '        <div>' +
        '          <span class="ag-giving-modal__badge"><i class="fas fa-seedling me-1"></i> Cheerful Giver</span>' +
        '          <h5 class="modal-title" id="agGivingStoryModalLabel">Share Why You Gave</h5>' +
        '          <p class="ag-giving-modal__sub mb-0">Your testimony of generosity can stir faith in someone else today.</p>' +
        "        </div>" +
        '        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>' +
        "      </div>" +
        '      <div class="modal-body ag-giving-modal__body">' +
        '        <div id="agGivingFormWrap">' +
        '          <form id="agGivingStoryForm" novalidate>' +
        '            <div class="row g-4">' +
        '              <div class="col-md-6">' +
        '                <label class="ag-giving-label" for="givingName">Full Name <span class="text-danger">*</span></label>' +
        '                <input type="text" class="form-control ag-giving-input" id="givingName" name="name" required autocomplete="name" placeholder="Your name">' +
        "              </div>" +
        '              <div class="col-md-6">' +
        '                <label class="ag-giving-label" for="givingEmail">Email <span class="ag-giving-label__optional">(optional)</span></label>' +
        '                <input type="email" class="form-control ag-giving-input" id="givingEmail" name="email" autocomplete="email" placeholder="you@email.com">' +
        "              </div>" +
        '              <div class="col-12">' +
        '                <span class="ag-giving-label d-block mb-2">What did you give toward? <span class="text-danger">*</span></span>' +
        '                <div class="ag-giving-categories" id="givingCategoryGroup" role="radiogroup" aria-label="Giving category">' +
        CATEGORIES.map(function (c, i) {
            return (
                '<label class="ag-giving-category">' +
                '  <input type="radio" name="category" value="' + c.id + '" class="ag-giving-category__input"' + (i === 0 ? " required" : "") + ">" +
                '  <span class="ag-giving-category__card">' +
                '    <i class="fas ' + c.icon + '"></i>' +
                "    " + c.label +
                "  </span>" +
                "</label>"
            );
        }).join("") +
        "                </div>" +
        "              </div>" +
        '              <div class="col-12">' +
        '                <label class="ag-giving-label" for="givingStory">Why did you give? <span class="text-danger">*</span></label>' +
        '                <div class="ag-giving-story-wrap">' +
        '                  <textarea class="form-control ag-giving-input ag-giving-textarea" id="givingStory" name="story" rows="6" required maxlength="' + MAX_STORY_LEN + '" placeholder="What moved your heart? How did God speak to you about giving? What do you hope your gift accomplishes for His kingdom?"></textarea>' +
        '                  <span class="ag-giving-char-count" id="givingCharCount">0 / ' + MAX_STORY_LEN + "</span>" +
        "                </div>" +
        "              </div>" +
        '              <div class="col-12">' +
        '                <span class="ag-giving-label d-block mb-2">How should we display your name?</span>' +
        '                <div class="ag-giving-privacy">' +
        '                  <label class="ag-giving-privacy__option"><input type="radio" name="privacy" value="full" checked required><span>Full name</span></label>' +
        '                  <label class="ag-giving-privacy__option"><input type="radio" name="privacy" value="first"><span>First name only</span></label>' +
        '                  <label class="ag-giving-privacy__option"><input type="radio" name="privacy" value="anonymous"><span>Anonymous giver</span></label>' +
        "                </div>" +
        "              </div>" +
        '              <div class="col-12">' +
        '                <div class="ag-giving-consent">' +
        '                  <input class="form-check-input" type="checkbox" id="givingConsent" name="consent" required>' +
        '                  <label class="form-check-label" for="givingConsent">I give AG Ikenebgu permission to review and may share my giving story on the website or in church publications.</label>' +
        "                </div>" +
        "              </div>" +
        "            </div>" +
        '            <div class="ag-giving-form-actions">' +
        '              <button type="submit" class="donate-hero__cta ag-giving-submit"><i class="fas fa-paper-plane"></i> Submit My Story</button>' +
        '              <button type="button" class="ag-giving-cancel" data-bs-dismiss="modal">Not now</button>' +
        "            </div>" +
        "          </form>" +
        "        </div>" +
        '        <div id="agGivingSuccess" class="ag-giving-success is-hidden" role="status">' +
        '          <div class="ag-giving-success__ring" aria-hidden="true"><i class="fas fa-heart"></i></div>' +
        "          <h4>Thank You for Sharing</h4>" +
        "          <p>Your giving story has been received with gratitude. May the Lord multiply every seed you sow.</p>" +
        '          <p class="ag-giving-success__verse mb-0"><em>\"It is more blessed to give than to receive.\" — Acts 20:35</em></p>' +
        '          <button type="button" class="donate-hero__cta mt-4" data-bs-dismiss="modal">Close</button>' +
        "        </div>" +
        "      </div>" +
        "    </div>" +
        "  </div>" +
        "</div>";

    function injectModal() {
        if (document.getElementById("agGivingStoryModal")) return;
        document.body.insertAdjacentHTML("beforeend", MODAL_HTML);
    }

    function escapeHtml(str) {
        var d = document.createElement("div");
        d.textContent = str;
        return d.innerHTML;
    }

    function getCategoryLabel(id) {
        var found = CATEGORIES.filter(function (c) { return c.id === id; })[0];
        return found ? found.label : id;
    }

    function displayName(entry) {
        if (entry.privacy === "anonymous") return "Anonymous Giver";
        if (entry.privacy === "first") {
            var parts = entry.name.trim().split(/\s+/);
            return parts[0] || "Anonymous Giver";
        }
        return entry.name.trim();
    }

    function storyCardHtml(entry) {
        return (
            '<blockquote class="donate-testimony-card donate-testimony-card--new">' +
            "  <p>" + escapeHtml(entry.story) + "</p>" +
            "  <cite>" + escapeHtml(displayName(entry)) +
            "<span>" + escapeHtml(getCategoryLabel(entry.category)) + " · " + escapeHtml(formatDate(entry.date)) + "</span></cite>" +
            "</blockquote>"
        );
    }

    function formatDate(iso) {
        return new Date(iso).toLocaleDateString("en-GB", { month: "short", year: "numeric" });
    }

    function renderSavedStories() {
        var grid = document.getElementById("donateGiverStories");
        if (!grid) return;

        try {
            var entries = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
            entries.slice().reverse().forEach(function (entry) {
                grid.insertAdjacentHTML("afterbegin", storyCardHtml(entry));
            });
        } catch (err) { /* optional */ }
    }

    function resetModal() {
        var formWrap = document.getElementById("agGivingFormWrap");
        var success = document.getElementById("agGivingSuccess");
        var form = document.getElementById("agGivingStoryForm");
        var count = document.getElementById("givingCharCount");
        if (formWrap) formWrap.classList.remove("is-hidden");
        if (success) success.classList.add("is-hidden");
        if (form) form.reset();
        if (count) count.textContent = "0 / " + MAX_STORY_LEN;
        document.querySelectorAll(".ag-giving-category__card").forEach(function (c) {
            c.classList.remove("is-selected");
        });
        var firstCat = document.querySelector(".ag-giving-category__input");
        if (firstCat) {
            firstCat.checked = true;
            if (firstCat.nextElementSibling) {
                firstCat.nextElementSibling.classList.add("is-selected");
            }
        }
    }

    function bindCharCount() {
        var textarea = document.getElementById("givingStory");
        var count = document.getElementById("givingCharCount");
        if (!textarea || !count) return;
        textarea.addEventListener("input", function () {
            count.textContent = textarea.value.length + " / " + MAX_STORY_LEN;
        });
    }

    function bindCategoryCards() {
        document.querySelectorAll(".ag-giving-category__input").forEach(function (input) {
            input.addEventListener("change", function () {
                document.querySelectorAll(".ag-giving-category__card").forEach(function (c) {
                    c.classList.remove("is-selected");
                });
                if (input.checked && input.nextElementSibling) {
                    input.nextElementSibling.classList.add("is-selected");
                }
            });
        });
        var first = document.querySelector(".ag-giving-category__input");
        if (first && first.checked && first.nextElementSibling) {
            first.nextElementSibling.classList.add("is-selected");
        }
    }

    function bindForm() {
        var form = document.getElementById("agGivingStoryForm");
        var modalEl = document.getElementById("agGivingStoryModal");
        if (!form || !modalEl) return;

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var categoryInput = form.querySelector('input[name="category"]:checked');
            if (!categoryInput) {
                categoryInput = form.querySelector('input[name="category"]');
                if (categoryInput) categoryInput.focus();
                return;
            }

            var privacyInput = form.querySelector('input[name="privacy"]:checked');
            var donorName = form.name.value.trim();
            if (privacyInput && privacyInput.value === "anonymous") {
                donorName = "Anonymous";
            } else if (privacyInput && privacyInput.value === "first") {
                donorName = donorName.split(/\s+/)[0] || donorName;
            }

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
            var apiBase = document.body.getAttribute("data-api-base") || "/api";
            var payload = {
                csrf_token: csrf,
                donor_name: donorName,
                testimony: form.story.value.trim(),
                category_slug: categoryInput.value === "tithes" ? "tithe" : (categoryInput.value === "building" ? "building_fund" : categoryInput.value)
            };

            var submitBtn = form.querySelector(".ag-giving-submit");
            if (submitBtn) submitBtn.disabled = true;

            fetch(apiBase.replace(/\/$/, "") + "/submit-testimony.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify(payload)
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) throw new Error((data && data.message) || "Submission failed.");
                    return data;
                });
            }).then(function () {
                var formWrap = document.getElementById("agGivingFormWrap");
                var success = document.getElementById("agGivingSuccess");
                if (formWrap) formWrap.classList.add("is-hidden");
                if (success) success.classList.remove("is-hidden");
            }).catch(function (err) {
                if (window.alert) window.alert(err.message || "Unable to submit story.");
            }).finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
        });

        modalEl.addEventListener("hidden.bs.modal", resetModal);
    }

    function init() {
        if (!document.body.classList.contains("donate-page")) return;
        injectModal();
        bindCharCount();
        bindCategoryCards();
        bindForm();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
