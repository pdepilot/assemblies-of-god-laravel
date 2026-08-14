/**
 * AGC Ikenegbu — Donate page (API + SSE + online giving)
 */
(function () {
    "use strict";

    var body = document.body;
    var API_BASE = (body && body.getAttribute("data-api-base")) || "/api";
    var CSRF = (body && body.getAttribute("data-csrf")) || "";
    var PAYSTACK_KEY = (body && body.getAttribute("data-paystack-key")) || "";
    var FLUTTERWAVE_KEY = (body && body.getAttribute("data-flutterwave-key")) || "";
    var FUND_SCOPE = (body && body.getAttribute("data-fund-scope")) || "church";
    if (FUND_SCOPE !== "church") {
        FUND_SCOPE = "church";
    }

    var CATEGORY_SLUGS = {
        offering: "offering",
        tithes: "tithe",
        tithe: "tithe",
        charity: "charity",
        building: "building_fund",
        building_fund: "building_fund",
        missions: "missions",
        project: "project_fund",
        partnership: "offering"
    };

    var state = {
        donors: [],
        categories: [],
        purposes: [],
        donorPage: 1,
        donorPages: 1,
        donorPerPage: 10,
        lastEventId: 0,
        lastDonorId: 0,
        sse: null,
        pollTimer: null
    };

    function readEmbeddedCategories() {
        if (!body) return [];
        try {
            var raw = body.getAttribute("data-categories");
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function getFallbackCategories() {
        return [
            { id: 1, slug: "offering", name: "Offering" },
            { id: 2, slug: "tithe", name: "Tithe" },
            { id: 3, slug: "charity", name: "Charity" },
            { id: 4, slug: "building_fund", name: "Building Fund" },
            { id: 5, slug: "project_fund", name: "Project Fund" }
        ];
    }

    function safeFetch(path) {
        return fetchJson(apiUrl(path)).catch(function () { return null; });
    }

    function apiUrl(path) {
        return API_BASE.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
    }

    function withScope(path) {
        var joiner = path.indexOf("?") >= 0 ? "&" : "?";
        return path + joiner + "scope=" + encodeURIComponent(FUND_SCOPE);
    }

    function escapeHtml(str) {
        var d = document.createElement("div");
        d.textContent = str == null ? "" : String(str);
        return d.innerHTML;
    }

    function formatAmount(n, currency) {
        var cur = currency || "NGN";
        if (cur === "NGN") return "₦" + Number(n || 0).toLocaleString("en-NG");
        return cur + " " + Number(n || 0).toLocaleString("en-US");
    }

    function formatDate(iso) {
        if (!iso) return "—";
        var normalized = String(iso).indexOf("T") === -1 ? String(iso).replace(" ", "T") : String(iso);
        // Date-only values — keep calendar day display.
        if (/^\d{4}-\d{2}-\d{2}$/.test(String(iso).trim())) {
            var dayOnly = new Date(iso + "T00:00:00");
            if (isNaN(dayOnly.getTime())) return iso;
            return dayOnly.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        }
        var d = new Date(normalized);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleString("en-GB", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true
        });
    }

    function fetchJson(url, options) {
        return fetch(url, options || {}).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw new Error((data && data.message) || "Request failed.");
                return data;
            });
        });
    }

    function dedupeDonors(list) {
        var seen = {};
        return (list || []).filter(function (d) {
            if (!d) return false;
            var key = d.id ? "id:" + d.id : [d.display_name, d.amount, d.donation_date, d.phone_masked].join("|");
            if (seen[key]) return false;
            seen[key] = true;
            return true;
        });
    }

    function syncLastDonorIdFromDonors() {
        state.donors.forEach(function (d) {
            if (d && d.id) {
                state.lastDonorId = Math.max(state.lastDonorId, Number(d.id));
            }
        });
    }

    function renderDonors(donors) {
        var tbody = document.getElementById("donorTableBody");
        if (!tbody) return;
        var list = dedupeDonors(donors || state.donors);
        state.donors = list;
        syncLastDonorIdFromDonors();
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:1.5rem">No recent donors yet. Be the first to give!</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(function (d) {
            var nameHtml = '<span class="donate-donor-name">' + escapeHtml(d.display_name) + "</span>";
            return (
                "<tr>" +
                '<td data-label="Donor">' + nameHtml + "</td>" +
                '<td data-label="Phone">' + escapeHtml(d.phone_masked || "****") + "</td>" +
                '<td data-label="Category"><span class="donate-donor-cat">' + escapeHtml(d.donation_type || d.category_name || "—") + "</span></td>" +
                '<td data-label="Amount">' + escapeHtml(formatAmount(d.amount, d.currency)) + "</td>" +
                '<td data-label="Time">' + escapeHtml(formatDate(d.given_at || d.donation_date)) + "</td>" +
                "</tr>"
            );
        }).join("");
    }

    function prependDonor(donor) {
        if (state.donorPage !== 1 || !donor) return;
        if (donor.id && state.donors.some(function (d) { return Number(d.id) === Number(donor.id); })) {
            state.lastDonorId = Math.max(state.lastDonorId, Number(donor.id));
            return;
        }
        state.donors.unshift(donor);
        if (donor.id) {
            state.lastDonorId = Math.max(state.lastDonorId, Number(donor.id));
        }
        if (state.donors.length > state.donorPerPage) state.donors.pop();
        renderDonors(state.donors);
    }

    function renderDonorPagination() {
        var info = document.getElementById("donorPageInfo");
        var prev = document.getElementById("donorPrev");
        var next = document.getElementById("donorNext");
        if (info) info.textContent = "Page " + state.donorPage + " of " + state.donorPages;
        if (prev) prev.disabled = state.donorPage <= 1;
        if (next) next.disabled = state.donorPage >= state.donorPages;
    }

    function loadDonorPage(page) {
        state.donorPage = Math.max(1, page || 1);
        return safeFetch(withScope("get-recent-donors.php?page=" + state.donorPage + "&per_page=" + state.donorPerPage)).then(function (res) {
            if (!res) return;
            state.donors = res.donors || [];
            state.donorPages = res.pages || 1;
            state.donorPage = res.page || state.donorPage;
            renderDonors(state.donors);
            renderDonorPagination();
        });
    }

    function renderCampaign(campaign) {
        if (!campaign || (!campaign.has_campaign && !(Number(campaign.id) > 0))) {
            var section = document.getElementById("campaign-tracker");
            if (section) {
                section.hidden = true;
                section.setAttribute("aria-hidden", "true");
            }
            return;
        }
        var section = document.getElementById("campaign-tracker");
        if (section) {
            section.hidden = false;
            section.removeAttribute("aria-hidden");
        }
        var panel = document.getElementById("donateCampaignProgress");
        if (panel) panel.hidden = false;

        var title = document.getElementById("campaignTitleName");
        var pct = document.getElementById("campaignProgressPct");
        var bar = document.getElementById("campaignProgressBar");
        var wrap = document.getElementById("campaignProgressBarWrap");
        var target = document.getElementById("campaignTarget");
        var raised = document.getElementById("campaignRaised");
        var remaining = document.getElementById("campaignRemaining");
        var donors = document.getElementById("campaignDonors");
        var countdown = document.getElementById("campaignCountdown");
        var label = document.getElementById("campaignProgressLabel");
        var heading = document.getElementById("campaignTitle");
        var progress = Number(campaign.progress_pct || 0);

        if (heading && campaign.title) heading.textContent = campaign.title;
        if (title) title.textContent = campaign.title || campaign.category_name || "";
        if (pct) pct.textContent = progress + "%";
        if (label) label.textContent = progress + "%";
        if (bar) bar.style.width = progress + "%";
        if (wrap) wrap.setAttribute("aria-valuenow", String(progress));
        if (target) target.textContent = formatAmount(campaign.target_amount, campaign.currency);
        if (raised) raised.textContent = formatAmount(campaign.raised_amount, campaign.currency);
        if (remaining) remaining.textContent = formatAmount(campaign.remaining_amount != null ? campaign.remaining_amount : Math.max(0, Number(campaign.target_amount || 0) - Number(campaign.raised_amount || 0)), campaign.currency);
        if (donors) donors.textContent = String(campaign.donor_count || 0);
        if (countdown) {
            if (campaign.days_remaining != null) {
                countdown.textContent = campaign.days_remaining + " day" + (campaign.days_remaining === 1 ? "" : "s");
            } else if (campaign.end_date) {
                countdown.textContent = campaign.end_date;
            } else {
                countdown.textContent = "Ongoing";
            }
        }
    }

    function renderCampaignsGrid(campaigns) {
        var grid = document.getElementById("campaignsGrid");
        if (!grid || !campaigns || !campaigns.length) return;
        if (campaigns.length <= 1) {
            grid.innerHTML = "";
            return;
        }
        grid.innerHTML = campaigns.map(function (c) {
            var pct = Number(c.progress_pct || 0);
            var remaining = c.remaining_amount != null ? c.remaining_amount : Math.max(0, Number(c.target_amount || 0) - Number(c.raised_amount || 0));
            var img = c.image_url ? '<img class="donate-campaign__image" src="' + escapeHtml(c.image_url) + '" alt="" loading="lazy">' : "";
            var donateHref = c.donation_link ? escapeHtml(c.donation_link) : "#give-online";
            var donateAttrs = c.donation_link ? ' target="_blank" rel="noopener"' : "";
            return (
                '<article class="donate-campaign donate-campaign--card">' +
                img +
                '<div class="donate-campaign__top"><h3 class="donate-campaign__name">' + escapeHtml(c.title) + '</h3><span class="donate-campaign__pct">' + pct + '%</span></div>' +
                '<div class="donate-campaign__bar" role="progressbar" aria-valuenow="' + pct + '"><div class="donate-campaign__fill" style="width:' + pct + '%"></div></div>' +
                '<p class="donate-campaign__desc">' + escapeHtml(c.description || "") + '</p>' +
                '<div class="donate-campaign__meta">' +
                '<div><span>Goal</span><strong>' + formatAmount(c.target_amount, c.currency) + '</strong></div>' +
                '<div><span>Raised</span><strong>' + formatAmount(c.raised_amount, c.currency) + '</strong></div>' +
                '<div><span>Remaining</span><strong>' + formatAmount(remaining, c.currency) + '</strong></div>' +
                '<div><span>Donors</span><strong>' + Number(c.donor_count || 0) + '</strong></div>' +
                '</div>' +
                '<a class="donate-btn donate-btn--primary donate-campaign__donate" href="' + donateHref + '"' + donateAttrs + '>Donate Now</a>' +
                '</article>'
            );
        }).join("");
    }

    function populatePurposes(purposes) {
        var sel = document.getElementById("onlinePurpose");
        if (!sel) return;
        var html = '<option value="">General Giving</option>';
        (purposes || []).forEach(function (p) {
            html += '<option value="' + escapeHtml(String(p.id)) + '" data-memorial="' + (p.is_memorial ? "1" : "0") + '" data-cert="' + (p.issues_certificate ? "1" : "0") + '">' + escapeHtml(p.purpose_name) + "</option>";
        });
        sel.innerHTML = html;
    }

    function bindPurposeFields() {
        var sel = document.getElementById("onlinePurpose");
        var panel = document.getElementById("dedicationFields");
        var nameWrap = document.getElementById("dedicateeNameWrap");
        var relWrap = document.getElementById("dedicateeRelationshipWrap");
        var nameLabel = nameWrap ? nameWrap.querySelector("label") : null;
        if (!sel || !panel) return;

        function sync() {
            var opt = sel.options[sel.selectedIndex];
            var hasPurpose = sel.value !== "";
            var isMemorial = opt && opt.getAttribute("data-memorial") === "1";
            panel.hidden = !hasPurpose;
            if (nameLabel) {
                nameLabel.textContent = isMemorial ? "In Loving Memory Of" : "Dedicated To";
            }
            if (relWrap) relWrap.hidden = !hasPurpose;
        }

        sel.addEventListener("change", sync);
        sync();
    }

    function populateOnlineCategories(categories) {
        var sel = document.getElementById("onlineCategory");
        if (!sel) return;
        var list = (categories && categories.length) ? categories : getFallbackCategories();
        state.categories = list;
        var html = '<option value="">Select category…</option>';
        list.forEach(function (cat) {
            html += '<option value="' + escapeHtml(String(cat.id)) + '">' + escapeHtml(cat.name) + "</option>";
        });
        sel.innerHTML = html;
    }

    function renderTestimonies(items) {
        var grid = document.getElementById("donateGiverStories");
        if (!grid || !items || !items.length) return;
        var fallback = grid.innerHTML;
        grid.innerHTML = items.map(function (t) {
            var cite = t.donor_name + (t.category_name ? "<span>" + escapeHtml(t.category_name) + "</span>" : "");
            return (
                '<blockquote class="donate-testimony-card">' +
                "<p>" + escapeHtml(t.testimony) + "</p>" +
                "<cite>" + cite + "</cite>" +
                "</blockquote>"
            );
        }).join("");
        if (!grid.innerHTML.trim()) grid.innerHTML = fallback;
    }

    function resolveCategorySlug(raw) {
        if (!raw) return "";
        return CATEGORY_SLUGS[raw] || raw;
    }

    function goToOnlineGiving(rawSlug) {
        var slug = resolveCategorySlug(rawSlug);
        var onlineSel = document.getElementById("onlineCategory");
        if (onlineSel && slug) {
            var match = (state.categories || []).filter(function (c) { return c.slug === slug; })[0];
            if (match) {
                onlineSel.value = String(match.id);
                onlineSel.dispatchEvent(new Event("change", { bubbles: true }));
            }
        }

        var target = document.getElementById("give-online");
        if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "start" });
        }

        var focusEl = document.getElementById("onlineDonorName") || document.getElementById("onlineAmount");
        if (focusEl) {
            window.setTimeout(function () {
                focusEl.focus({ preventScroll: true });
            }, 450);
        }
    }

    function bindCategories() {
        document.querySelectorAll(".donate-category-card").forEach(function (card) {
            card.addEventListener("click", function () {
                document.querySelectorAll(".donate-category-card").forEach(function (c) {
                    c.classList.remove("is-active");
                    c.setAttribute("aria-pressed", "false");
                });
                card.classList.add("is-active");
                card.setAttribute("aria-pressed", "true");
                goToOnlineGiving(card.getAttribute("data-category-slug"));
            });
        });
    }

    function loadInitialData() {
        state.categories = readEmbeddedCategories();
        if (state.categories.length) {
            populateOnlineCategories(state.categories);
        }

        var priority = loadDonorPage(1);

        Promise.all([
            safeFetch(withScope("get-categories.php")),
            safeFetch(withScope("get-campaign-progress.php")),
            safeFetch("get-testimonies.php?limit=6"),
            safeFetch("get-donation-purposes.php"),
            safeFetch(withScope("get-campaigns.php"))
        ]).then(function (results) {
            if (results[0] && results[0].categories && results[0].categories.length) {
                state.categories = results[0].categories;
                populateOnlineCategories(state.categories);
            } else if (!state.categories.length) {
                populateOnlineCategories(getFallbackCategories());
            }

            if (results[1] && results[1].campaign) {
                renderCampaign(results[1].campaign);
            }

            if (results[2] && results[2].testimonies) {
                renderTestimonies(results[2].testimonies);
            }

            if (results[3] && results[3].purposes) {
                state.purposes = results[3].purposes;
                populatePurposes(state.purposes);
                bindPurposeFields();
            }

            if (results[4] && results[4].campaigns) {
                renderCampaignsGrid(results[4].campaigns);
            }
        });

        return priority;
    }

    function handleSsePayload(eventType, raw) {
        try {
            var data = JSON.parse(raw);
            if (eventType === "donor" && data.donor) {
                prependDonor(data.donor);
            }
            if (eventType === "donation" || (data.type === "feed")) {
                if (data.id) state.lastEventId = Math.max(state.lastEventId, Number(data.id));
                if (data.donation_id) {
                    state.lastDonorId = Math.max(state.lastDonorId, Number(data.donation_id));
                }
                // Keep table current if a structured donor payload was not sent.
                if (!data.donor && state.donorPage === 1) {
                    loadDonorPage(1);
                }
            }
            if (eventType === "stats" || data.type === "stats") {
                if (data.campaign) renderCampaign(data.campaign);
            }
        } catch (err) { /* ignore malformed */ }
    }

    function startPolling() {
        if (state.pollTimer) return;
        state.pollTimer = setInterval(function () {
            loadDonorPage(state.donorPage);
            fetchJson(apiUrl(withScope("get-campaign-progress.php"))).then(function (data) {
                renderCampaign(data.campaign);
            }).catch(function () { /* silent */ });
        }, 15000);
    }

    function connectSse() {
        if (typeof EventSource === "undefined") {
            startPolling();
            return;
        }

        var url = apiUrl(withScope("live-donors.php?last_event_id=" + state.lastEventId + "&last_donor_id=" + state.lastDonorId));
        try {
            state.sse = new EventSource(url);
        } catch (err) {
            startPolling();
            return;
        }

        state.sse.addEventListener("donor", function (e) { handleSsePayload("donor", e.data); });
        state.sse.addEventListener("donation", function (e) { handleSsePayload("donation", e.data); });
        state.sse.addEventListener("stats", function (e) { handleSsePayload("stats", e.data); });
        state.sse.addEventListener("reconnect", function () {
            if (state.sse) state.sse.close();
            setTimeout(connectSse, 1000);
        });
        state.sse.onerror = function () {
            if (state.sse) state.sse.close();
            state.sse = null;
            startPolling();
            setTimeout(connectSse, 5000);
        };
    }

    function setOnlineStatus(msg, isError) {
        var el = document.getElementById("donateOnlineStatus");
        if (!el) return;
        el.textContent = msg || "";
        el.className = "donate-online-note" + (isError ? " is-error" : msg ? " is-success" : "");
    }

    var checkoutOverlayEl = null;
    var paystackScriptPromise = null;

    function ensureCheckoutOverlay() {
        if (checkoutOverlayEl) return checkoutOverlayEl;
        checkoutOverlayEl = document.createElement("div");
        checkoutOverlayEl.id = "donateCheckoutOverlay";
        checkoutOverlayEl.className = "donate-checkout-overlay";
        checkoutOverlayEl.setAttribute("role", "status");
        checkoutOverlayEl.setAttribute("aria-live", "polite");
        checkoutOverlayEl.innerHTML =
            '<div class="donate-checkout-overlay__panel">' +
            '  <div class="donate-checkout-overlay__spinner" aria-hidden="true"></div>' +
            '  <p class="donate-checkout-overlay__title">Opening secure checkout…</p>' +
            '  <p class="donate-checkout-overlay__hint">This only takes a moment.</p>' +
            "</div>";
        document.body.appendChild(checkoutOverlayEl);
        return checkoutOverlayEl;
    }

    function showCheckoutOverlay(title) {
        var el = ensureCheckoutOverlay();
        var titleEl = el.querySelector(".donate-checkout-overlay__title");
        if (titleEl && title) titleEl.textContent = title;
        el.classList.add("is-active");
        document.body.classList.add("donate-checkout-active");
    }

    function hideCheckoutOverlay() {
        if (checkoutOverlayEl) checkoutOverlayEl.classList.remove("is-active");
        document.body.classList.remove("donate-checkout-active");
    }

    function ensurePaystackScript() {
        if (window.PaystackPop) return Promise.resolve();
        if (paystackScriptPromise) return paystackScriptPromise;
        paystackScriptPromise = new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[src*="js.paystack.co/v1/inline.js"]');
            if (existing) {
                existing.addEventListener("load", function () { resolve(); }, { once: true });
                existing.addEventListener("error", function () { reject(new Error("Paystack script failed")); }, { once: true });
                return;
            }
            var script = document.createElement("script");
            script.src = "https://js.paystack.co/v1/inline.js";
            script.async = true;
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error("Paystack script failed")); };
            document.head.appendChild(script);
        });
        return paystackScriptPromise;
    }

    function preloadCheckoutAssets() {
        if (PAYSTACK_KEY) ensurePaystackScript().catch(function () {});
    }

    function launchPaystackCheckout(intent, form, callbackUrl, btn) {
        var email = form.donor_email.value.trim();
        var amount = Math.round(Number(form.amount.value) * 100);
        var ref = intent.gateway_reference || intent.intent_ref;
        var key = PAYSTACK_KEY || intent.public_key;

        function redirectToPaystack(intent) {
            if (intent.access_code) {
                window.location.replace("https://checkout.paystack.com/" + encodeURIComponent(intent.access_code));
                return true;
            }
            if (intent.authorization_url) {
                window.location.replace(intent.authorization_url);
                return true;
            }
            return false;
        }

        function openPopup() {
            hideCheckoutOverlay();
            if (!window.PaystackPop || !key) {
                if (redirectToPaystack(intent)) return;
                setOnlineStatus("Unable to open Paystack checkout.", true);
                if (btn) btn.disabled = false;
                return;
            }

            var options = {
                key: key,
                email: email,
                amount: amount,
                ref: ref,
                onClose: function () {
                    if (btn) btn.disabled = false;
                    setOnlineStatus("Payment window closed. You can try again.", true);
                },
                callback: function (response) {
                    var sep = callbackUrl.indexOf("?") >= 0 ? "&" : "?";
                    window.location.replace(callbackUrl + sep + "reference=" + encodeURIComponent(response.reference));
                }
            };

            if (intent.access_code) {
                options.access_code = intent.access_code;
            }

            PaystackPop.setup(options).openIframe();
        }

        if (window.PaystackPop) {
            openPopup();
            return;
        }

        ensurePaystackScript().then(openPopup).catch(function () {
            if (redirectToPaystack(intent)) return;
            hideCheckoutOverlay();
            setOnlineStatus("Unable to load Paystack checkout.", true);
            if (btn) btn.disabled = false;
        });
    }

    function launchFlutterwaveCheckout(intent, btn) {
        var url = intent.authorization_url;
        if (!url) {
            hideCheckoutOverlay();
            setOnlineStatus("Payment gateway did not return a checkout URL.", true);
            if (btn) btn.disabled = false;
            return;
        }
        window.location.replace(url);
    }

    function openCheckout(intent, provider, form, callbackUrl, btn) {
        provider = (provider || "paystack").toLowerCase();
        if (provider === "paystack") {
            launchPaystackCheckout(intent, form, callbackUrl, btn);
        } else {
            launchFlutterwaveCheckout(intent, btn);
        }
    }

    function bindDonationTypeToggle() {
        var fields = document.getElementById("recurringFields");
        var start = document.getElementById("recurringStart");
        if (start && !start.value) start.value = new Date().toISOString().slice(0, 10);

        document.querySelectorAll('input[name="donation_type"]').forEach(function (input) {
            input.addEventListener("change", function () {
                if (fields) fields.hidden = input.value !== "recurring" || !input.checked;
            });
        });
    }

    function bindPledgeForm() {
        var form = document.getElementById("donatePledgeForm");
        if (!form) return;
        var start = document.getElementById("pledgeStart");
        if (start && !start.value) start.value = new Date().toISOString().slice(0, 10);

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }
            var status = document.getElementById("pledgeFormStatus");
            var buildingCat = (state.categories || []).filter(function (c) { return c.slug === "building_fund"; })[0];
            var payload = {
                csrf_token: CSRF,
                donor_name: form.donor_name.value.trim(),
                donor_email: form.donor_email.value.trim(),
                donor_phone: form.donor_phone.value.trim(),
                pledged_amount: Number(form.pledged_amount.value),
                installment_count: Number(form.installment_count.value) || null,
                start_date: form.start_date.value,
                category_id: buildingCat ? buildingCat.id : null
            };
            fetchJson(apiUrl("create-pledge.php"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            }).then(function (res) {
                if (status) status.textContent = res.message || "Pledge submitted.";
                form.reset();
                if (start) start.value = new Date().toISOString().slice(0, 10);
            }).catch(function (err) {
                if (status) status.textContent = err.message || "Unable to submit pledge.";
            });
        });
    }

    function bindOnlineForm() {
        var form = document.getElementById("donateOnlineForm");
        if (!form) return;

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (!form.category_id.value) {
                setOnlineStatus("Please select a donation category.", true);
                form.category_id.focus();
                return;
            }

            var amount = Number(form.amount.value);
            if (!amount || amount < 100) {
                setOnlineStatus("Minimum online gift is ₦100.", true);
                form.amount.focus();
                return;
            }

            var btn = document.getElementById("onlineDonateBtn");
            if (btn) btn.disabled = true;
            showCheckoutOverlay("Opening secure checkout…");

            var isRecurring = form.querySelector('input[name="donation_type"]:checked');
            var recurring = isRecurring && isRecurring.value === "recurring";
            var endpoint = recurring ? "create-recurring-donation.php" : "create-donation.php";
            var provider = form.payment_provider.value;
            var baseUrl = window.location.href.split("?")[0];
            var returnStatus = recurring ? "recurring_complete" : "complete";
            var checkoutStarted = false;

            var payload = {
                csrf_token: CSRF,
                donor_name: form.donor_name.value.trim(),
                donor_email: form.donor_email.value.trim(),
                donor_phone: form.donor_phone.value.trim(),
                donor_location: (form.donor_location && form.donor_location.value) ? form.donor_location.value.trim() : "",
                amount: amount,
                category_id: Number(form.category_id.value),
                payment_provider: provider,
                is_anonymous: document.getElementById("onlineAnonymous") && document.getElementById("onlineAnonymous").checked ? 1 : 0,
                fund_scope: FUND_SCOPE,
                callback_url: baseUrl + "?status=" + returnStatus + "&provider=" + encodeURIComponent(provider)
            };

            if (recurring) {
                payload.frequency = form.frequency.value;
                payload.start_date = form.start_date.value || new Date().toISOString().slice(0, 10);
                payload.end_date = form.end_date.value || "";
                payload.max_occurrences = form.max_occurrences.value ? Number(form.max_occurrences.value) : null;
            }

            var purposeSel = document.getElementById("onlinePurpose");
            if (purposeSel && purposeSel.value) {
                payload.purpose_id = Number(purposeSel.value);
                payload.dedicatee_name = (document.getElementById("dedicateeName") || {}).value || "";
                payload.dedicatee_relationship = (document.getElementById("dedicateeRelationship") || {}).value || "";
                payload.dedication_message = (document.getElementById("dedicationMessage") || {}).value || "";
            }

            fetchJson(apiUrl(endpoint), {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify(payload)
            }).then(function (res) {
                var intent = res.data || {};
                if (!intent.authorization_url && !intent.access_code) {
                    hideCheckoutOverlay();
                    setOnlineStatus(res.message || "Payment gateway did not return a checkout URL.", true);
                    return;
                }
                checkoutStarted = true;
                openCheckout(intent, provider, form, payload.callback_url, btn);
            }).catch(function (err) {
                hideCheckoutOverlay();
                setOnlineStatus(err.message || "Unable to start payment.", true);
            }).finally(function () {
                if (btn && !checkoutStarted) btn.disabled = false;
            });
        });
    }

    function bindSponsorshipForm() {
        var form = document.getElementById("donateSponsorshipForm");
        if (!form) return;
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!form.checkValidity()) { form.reportValidity(); return; }
            var status = document.getElementById("sponsorshipFormStatus");
            fetchJson(apiUrl("create-sponsorship.php"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    csrf_token: CSRF,
                    sponsor_name: form.sponsor_name.value.trim(),
                    sponsor_email: form.sponsor_email.value.trim(),
                    sponsor_phone: form.sponsor_phone.value.trim(),
                    sponsorship_type: form.sponsorship_type.value,
                    beneficiary_name: form.beneficiary_name.value.trim(),
                    monthly_amount: Number(form.monthly_amount.value),
                    duration_months: form.duration_months.value ? Number(form.duration_months.value) : null
                })
            }).then(function (res) {
                if (status) status.textContent = res.message || "Sponsorship registered.";
                form.reset();
            }).catch(function (err) {
                if (status) status.textContent = err.message || "Unable to register sponsorship.";
            });
        });
    }

    function checkReturnStatus() {
        var params = new URLSearchParams(window.location.search);
        var status = params.get("status");
        var provider = (params.get("provider") || "paystack").toLowerCase();
        var reference = params.get("reference") || params.get("trxref") || params.get("tx_ref") || "";
        var transactionId = params.get("transaction_id") || "";

        if (status === "complete" && reference) {
            setOnlineStatus("Verifying your payment…");
            var verifyUrl = "verify-donation.php?reference=" + encodeURIComponent(reference)
                + "&provider=" + encodeURIComponent(provider);
            if (transactionId) {
                verifyUrl += "&transaction_id=" + encodeURIComponent(transactionId);
            }
            fetchJson(apiUrl(verifyUrl))
                .then(function (res) {
                    setOnlineStatus(res.message || "Thank you! Your gift has been recorded.", false);
                    loadInitialData();
                })
                .catch(function (err) {
                    setOnlineStatus(err.message || "Payment received — confirmation is processing.", false);
                });
        } else if (status === "complete") {
            setOnlineStatus("Thank you! If your payment was successful, your gift will appear shortly after verification.", false);
        } else if (status === "recurring_complete") {
            setOnlineStatus("Thank you! Your recurring giving setup is processing. You will receive confirmation once verified.", false);
        } else {
            return;
        }

        var modalBtn = document.querySelector('[data-bs-target="#agGivingStoryModal"]');
        if (modalBtn && window.bootstrap && (status === "complete" || status === "recurring_complete")) {
            setTimeout(function () {
                modalBtn.click();
            }, 1200);
        }

        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    function animateCounters() {
        document.querySelectorAll(".donate-stat__num").forEach(function (el) {
            var target = parseInt(el.getAttribute("data-count"), 10);
            if (!target || el.dataset.done) return;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    el.dataset.done = "1";
                    observer.disconnect();
                    var dur = 1800;
                    var t0 = performance.now();
                    function tick(now) {
                        var p = Math.min((now - t0) / dur, 1);
                        var eased = 1 - Math.pow(1 - p, 3);
                        el.textContent = Math.floor(target * eased).toLocaleString();
                        if (p < 1) requestAnimationFrame(tick);
                        else el.textContent = target.toLocaleString() + (el.getAttribute("data-suffix") || "");
                    }
                    requestAnimationFrame(tick);
                });
            }, { threshold: 0.35 });
            observer.observe(el);
        });
    }

    function initReveal() {
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            document.querySelectorAll(".donate-reveal").forEach(function (el) { el.classList.add("is-visible"); });
            return;
        }
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add("is-visible");
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
        document.querySelectorAll(".donate-reveal").forEach(function (el) { obs.observe(el); });
    }

    function initParallax() {
        var hero = document.querySelector(".donate-hero__bg");
        if (!hero || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
        window.addEventListener("scroll", function () {
            hero.style.transform = "translate3d(0, " + (window.scrollY * 0.35) + "px, 0) scale(1.08)";
        }, { passive: true });
    }

    function init() {
        preloadCheckoutAssets();
        populateOnlineCategories(readEmbeddedCategories());
        bindCategories();
        bindDonationTypeToggle();
        bindOnlineForm();
        bindPledgeForm();
        bindSponsorshipForm();
        animateCounters();
        initReveal();
        initParallax();
        checkReturnStatus();

        var donorPrev = document.getElementById("donorPrev");
        var donorNext = document.getElementById("donorNext");
        if (donorPrev) donorPrev.addEventListener("click", function () { if (state.donorPage > 1) loadDonorPage(state.donorPage - 1); });
        if (donorNext) donorNext.addEventListener("click", function () { if (state.donorPage < state.donorPages) loadDonorPage(state.donorPage + 1); });

        loadInitialData().then(function () {
            syncLastDonorIdFromDonors();
            connectSse();
        });

        document.querySelectorAll('a[href="#give-categories"]').forEach(function (link) {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                var target = document.getElementById("give-categories");
                if (target) target.scrollIntoView({ behavior: "smooth" });
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
