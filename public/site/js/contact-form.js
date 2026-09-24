/**
 * AGC Ikenegbu — public contact form submission
 */
(function () {
    'use strict';

    function boot() {
        var form = document.querySelector('.contact-form');
        if (!form || form.getAttribute('data-bound') === '1') return;
        form.setAttribute('data-bound', '1');

        var tabs = document.querySelectorAll('.contact-type-tab');
        var subject = document.getElementById('contactSubject');
        var typeInput = document.getElementById('contactInquiryType');
        var alertBox = document.getElementById('contactFormAlert') || document.getElementById('cmsContactAlert');
        var submitBtn = form.querySelector('button[type="submit"]');
        var popup = document.getElementById('contactSuccessPopup');
        var popupRef = document.getElementById('contactSuccessRef');
        var sending = false;
        var idleLabel = submitBtn ? submitBtn.innerHTML : '';

        var placeholders = {
            general: 'How can we help you?',
            prayer: 'Prayer request topic',
            visit: 'Preferred visit date / questions'
        };

        var activeType = 'general';
        var apiBase = form.getAttribute('data-submit-url') || form.getAttribute('data-api') || '/api/contact';
        if (apiBase.indexOf('handlers/contact-handler') !== -1) {
            apiBase = '/api/contact';
        }

        function csrfToken() {
            var input = form.querySelector('input[name="_token"]');
            if (input && input.value) return input.value;
            var meta = document.querySelector('meta[name="csrf-token"]');
            return (meta && meta.getAttribute('content')) || '';
        }

        function showAlert(message, type) {
            if (!alertBox) return;
            alertBox.className = 'contact-form-alert contact-form-alert--' + (type || 'info');
            alertBox.innerHTML = message;
            alertBox.classList.remove('is-hidden');
        }

        function hideAlert() {
            if (alertBox) alertBox.classList.add('is-hidden');
        }

        function setLoading(loading) {
            sending = loading;
            if (!submitBtn) return;
            submitBtn.disabled = loading;
            submitBtn.setAttribute('aria-busy', loading ? 'true' : 'false');
            submitBtn.innerHTML = loading
                ? '<i class="fa fa-spinner fa-spin me-2"></i>Sending…'
                : (idleLabel || '<i class="fa fa-paper-plane me-2"></i>Send Message');
        }

        function openSuccess(reference) {
            if (!popup) {
                showAlert(
                    '<strong>Thank you!</strong> Your message has been received' +
                    (reference ? '. Reference: <code>' + escapeHtml(reference) + '</code>' : '') +
                    '.',
                    'success'
                );
                return;
            }
            if (popupRef) {
                if (reference) {
                    popupRef.hidden = false;
                    popupRef.textContent = 'Reference: ' + reference;
                } else {
                    popupRef.hidden = true;
                    popupRef.textContent = '';
                }
            }
            popup.hidden = false;
            popup.classList.add('is-open');
            document.body.classList.add('contact-popup-open');
            var closeBtn = popup.querySelector('[data-close-success].btn, button[data-close-success]');
            if (closeBtn) closeBtn.focus();
        }

        function closeSuccess() {
            if (!popup) return;
            popup.classList.remove('is-open');
            popup.hidden = true;
            document.body.classList.remove('contact-popup-open');
        }

        function setActiveType(type) {
            activeType = type;
            if (typeInput) typeInput.value = type;
            tabs.forEach(function (tab) {
                var on = tab.dataset.type === type;
                tab.classList.toggle('active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            if (subject && placeholders[type]) {
                subject.placeholder = placeholders[type];
            }
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                setActiveType(tab.dataset.type || 'general');
            });
        });

        if (popup) {
            popup.querySelectorAll('[data-close-success]').forEach(function (el) {
                el.addEventListener('click', closeSuccess);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSuccess();
            });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                if (form.checkValidity()) {
                    setLoading(true);
                }
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.getAttribute('data-inflight') === '1') {
                return;
            }
            hideAlert();

            if (!form.checkValidity()) {
                setLoading(false);
                form.reportValidity();
                return;
            }

            setLoading(true);
            form.setAttribute('data-inflight', '1');

            var body = new FormData(form);
            body.set('action', 'submit');
            body.set('inquiry_type', activeType);

            fetch(apiBase, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: body
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || 'Unable to send your message.');
                        }
                        return data;
                    }, function () {
                        throw new Error('Unable to send your message. Please try again.');
                    });
                })
                .then(function (res) {
                    form.reset();
                    setActiveType('general');
                    openSuccess(res.reference || '');
                    if (window.AG_ANALYTICS && typeof window.AG_ANALYTICS.event === 'function') {
                        window.AG_ANALYTICS.event('contact_form_submit', { form_name: 'contact' });
                    }
                })
                .catch(function (err) {
                    showAlert(escapeHtml(err.message || 'Something went wrong. Please try again or call the church office.'), 'error');
                })
                .finally(function () {
                    form.removeAttribute('data-inflight');
                    setLoading(false);
                });
        });

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        setActiveType('general');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
