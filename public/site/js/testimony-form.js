/**
 * AGC Ikenegbu — testimony modal (injected once) + form handling
 */
(function () {
    "use strict";

    var MAX_PHOTO_BYTES = 2 * 1024 * 1024;
    var PHOTO_MAX_DIM = 480;
    var PHOTO_JPEG_QUALITY = 0.82;
    var currentPhotoData = null;

    var MODAL_HTML =
        '<div class="modal fade ag-testimony-modal" id="agTestimonyModal" tabindex="-1" aria-labelledby="agTestimonyModalLabel" aria-hidden="true">' +
        '  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">' +
        '    <div class="modal-content ag-testimony-modal__content">' +
        '      <div class="ag-testimony-modal__accent" aria-hidden="true"></div>' +
        '      <div class="modal-header ag-testimony-modal__header">' +
        '        <div>' +
        '          <span class="ag-testimony-modal__badge">Share Your Story</span>' +
        '          <h5 class="modal-title" id="agTestimonyModalLabel">Write Your Testimony</h5>' +
        '          <p class="ag-testimony-modal__sub mb-0">How has God worked in your life through AGC Ikenegbu?</p>' +
        "        </div>" +
        '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
        "      </div>" +
        '      <div class="modal-body ag-testimony-modal__body">' +
        '        <div id="agTestimonyFormWrap">' +
        '          <form id="agTestimonyForm" novalidate>' +
        '            <div class="row g-3">' +
        '              <div class="col-md-6">' +
        '                <label class="form-label" for="testimonyName">Full Name <span class="text-danger">*</span></label>' +
        '                <input type="text" class="form-control ag-testimony-input" id="testimonyName" name="name" required autocomplete="name" placeholder="Your name">' +
        "              </div>" +
        '              <div class="col-md-6">' +
        '                <label class="form-label" for="testimonyEmail">Email <span class="text-danger">*</span></label>' +
        '                <input type="email" class="form-control ag-testimony-input" id="testimonyEmail" name="email" required autocomplete="email" placeholder="you@email.com">' +
        "              </div>" +
        '              <div class="col-12">' +
        '                <label class="form-label" for="testimonyRole">Role / Ministry (optional)</label>' +
        '                <input type="text" class="form-control ag-testimony-input" id="testimonyRole" name="role" placeholder="e.g. Church Member, Youth Leader">' +
        "              </div>" +
        '              <div class="col-12">' +
        '                <label class="form-label" for="testimonyPhoto">Your Photo <span class="text-muted fw-normal">(optional)</span></label>' +
        '                <div class="ag-testimony-photo">' +
        '                  <div class="ag-testimony-photo__preview is-hidden" id="testimonyPhotoPreview">' +
        '                    <img id="testimonyPhotoImg" src="" alt="Photo preview">' +
        '                    <button type="button" class="ag-testimony-photo__remove" id="testimonyPhotoRemove" aria-label="Remove photo"><i class="fas fa-times"></i> Remove photo</button>' +
        "                  </div>" +
        '                  <label class="ag-testimony-photo__drop" id="testimonyPhotoDrop" for="testimonyPhoto">' +
        '                    <span class="ag-testimony-photo__icon" aria-hidden="true"><i class="fas fa-camera"></i></span>' +
        '                    <span class="ag-testimony-photo__text"><strong>Click to upload</strong> or drag your image here</span>' +
        '                    <span class="ag-testimony-photo__hint">JPG, PNG, or WebP — max 2 MB</span>' +
        '                    <input type="file" class="ag-testimony-photo__input" id="testimonyPhoto" name="photo" accept="image/jpeg,image/png,image/webp">' +
        "                  </label>" +
        '                  <p class="ag-testimony-photo__error is-hidden" id="testimonyPhotoError" role="alert"></p>' +
        "                </div>" +
        "              </div>" +
        '              <div class="col-12">' +
        '                <label class="form-label" for="testimonyText">Your Testimony <span class="text-danger">*</span></label>' +
        '                <textarea class="form-control ag-testimony-input" id="testimonyText" name="testimony" rows="6" required placeholder="Share what Christ has done — salvation, healing, provision, answered prayer, or growth in faith…"></textarea>' +
        "              </div>" +
        '              <div class="col-12">' +
        '                <div class="form-check">' +
        '                  <input class="form-check-input" type="checkbox" id="testimonyConsent" name="consent" required>' +
        '                  <label class="form-check-label" for="testimonyConsent">I give AGC Ikenegbu permission to review and may share my testimony on the website or in church services.</label>' +
        "                </div>" +
        "              </div>" +
        "            </div>" +
        '            <div class="d-flex flex-wrap gap-3 mt-4">' +
        '              <button type="submit" class="btn btn-primary px-4 py-2">Submit Testimony</button>' +
        '              <button type="button" class="btn btn-outline-dark px-4 py-2" data-bs-dismiss="modal">Cancel</button>' +
        "            </div>" +
        "          </form>" +
        "        </div>" +
        '        <div id="agTestimonySuccess" class="ag-testimony-success is-hidden" role="status">' +
        '          <div class="ag-testimony-success__icon" aria-hidden="true"><i class="fas fa-praying-hands"></i></div>' +
        "          <h4>Thank You for Sharing</h4>" +
        "          <p>Your testimony has been received. Our team will prayerfully review it and may contact you before publishing.</p>" +
        '          <p class="mb-0"><em>\"Let the redeemed of the Lord say so.\" — Psalm 107:2</em></p>' +
        '          <button type="button" class="btn btn-primary mt-4" data-bs-dismiss="modal">Close</button>' +
        "        </div>" +
        "      </div>" +
        "    </div>" +
        "  </div>" +
        "</div>";

    function injectModal() {
        if (document.getElementById("agTestimonyModal")) return;
        document.body.insertAdjacentHTML("beforeend", MODAL_HTML);
    }

    function resetModal() {
        var formWrap = document.getElementById("agTestimonyFormWrap");
        var success = document.getElementById("agTestimonySuccess");
        var form = document.getElementById("agTestimonyForm");
        if (formWrap) formWrap.classList.remove("is-hidden");
        if (success) success.classList.add("is-hidden");
        if (form) form.reset();
        clearPhoto();
    }

    function showPhotoError(msg) {
        var el = document.getElementById("testimonyPhotoError");
        if (!el) return;
        if (msg) {
            el.textContent = msg;
            el.classList.remove("is-hidden");
        } else {
            el.textContent = "";
            el.classList.add("is-hidden");
        }
    }

    function setPhotoPreview(dataUrl) {
        var preview = document.getElementById("testimonyPhotoPreview");
        var img = document.getElementById("testimonyPhotoImg");
        var drop = document.getElementById("testimonyPhotoDrop");
        if (!preview || !img || !drop) return;
        if (dataUrl) {
            img.src = dataUrl;
            preview.classList.remove("is-hidden");
            drop.classList.add("is-hidden");
        } else {
            img.removeAttribute("src");
            preview.classList.add("is-hidden");
            drop.classList.remove("is-hidden");
        }
    }

    function clearPhoto() {
        currentPhotoData = null;
        var input = document.getElementById("testimonyPhoto");
        if (input) input.value = "";
        setPhotoPreview(null);
        showPhotoError("");
    }

    function resizeImageToDataUrl(file, callback) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var w = img.width;
                var h = img.height;
                var max = PHOTO_MAX_DIM;
                if (w > max || h > max) {
                    if (w >= h) {
                        h = Math.round((h * max) / w);
                        w = max;
                    } else {
                        w = Math.round((w * max) / h);
                        h = max;
                    }
                }
                var canvas = document.createElement("canvas");
                canvas.width = w;
                canvas.height = h;
                var ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, w, h);
                callback(canvas.toDataURL("image/jpeg", PHOTO_JPEG_QUALITY));
            };
            img.onerror = function () {
                callback(null);
            };
            img.src = e.target.result;
        };
        reader.onerror = function () {
            callback(null);
        };
        reader.readAsDataURL(file);
    }

    function handlePhotoFile(file) {
        showPhotoError("");
        if (!file) {
            clearPhoto();
            return;
        }
        if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) {
            showPhotoError("Please choose a JPG, PNG, or WebP image.");
            clearPhoto();
            return;
        }
        if (file.size > MAX_PHOTO_BYTES) {
            showPhotoError("Image must be 2 MB or smaller.");
            clearPhoto();
            return;
        }
        resizeImageToDataUrl(file, function (dataUrl) {
            if (!dataUrl) {
                showPhotoError("Could not read that image. Please try another file.");
                clearPhoto();
                return;
            }
            currentPhotoData = dataUrl;
            setPhotoPreview(dataUrl);
        });
    }

    function bindPhotoUpload() {
        var input = document.getElementById("testimonyPhoto");
        var removeBtn = document.getElementById("testimonyPhotoRemove");
        var drop = document.getElementById("testimonyPhotoDrop");
        if (!input) return;

        input.addEventListener("change", function () {
            handlePhotoFile(input.files && input.files[0]);
        });

        if (removeBtn) {
            removeBtn.addEventListener("click", function () {
                clearPhoto();
            });
        }

        if (drop) {
            ["dragenter", "dragover"].forEach(function (ev) {
                drop.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    drop.classList.add("is-dragover");
                });
            });
            ["dragleave", "drop"].forEach(function (ev) {
                drop.addEventListener(ev, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    drop.classList.remove("is-dragover");
                });
            });
            drop.addEventListener("drop", function (e) {
                var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (file) handlePhotoFile(file);
            });
        }
    }

    function bindForm() {
        var form = document.getElementById("agTestimonyForm");
        var modalEl = document.getElementById("agTestimonyModal");
        if (!form || !modalEl) return;

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting…';
            }

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
            var sourcePage = document.body.getAttribute("data-testimony-source") || "index";

            var payload = {
                csrf_token: csrf,
                name: form.name.value.trim(),
                email: form.email.value.trim(),
                role: form.role.value.trim(),
                testimony: form.testimony.value.trim(),
                source_page: sourcePage
            };
            if (currentPhotoData) {
                payload.photo = currentPhotoData;
            }

            fetch("/api/testimony/submit", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: JSON.stringify(payload)
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) throw new Error((data && data.message) || "Submission failed.");
                    return data;
                });
            }).then(function () {
                var formWrap = document.getElementById("agTestimonyFormWrap");
                var success = document.getElementById("agTestimonySuccess");
                if (formWrap) formWrap.classList.add("is-hidden");
                if (success) success.classList.remove("is-hidden");
            }).catch(function (err) {
                if (window.alert) window.alert(err.message || "Unable to submit testimony.");
            }).finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = "Submit Testimony";
                }
            });
        });

        modalEl.addEventListener("hidden.bs.modal", resetModal);
    }

    function init() {
        injectModal();
        bindPhotoUpload();
        bindForm();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
