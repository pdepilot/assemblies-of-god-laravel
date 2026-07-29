/**
 * AG footer / site newsletter subscribe
 */
(function () {
    'use strict';

    function bindForm(form) {
        if (!form || form.getAttribute('data-newsletter-bound') === '1') {
            return;
        }
        form.setAttribute('data-newsletter-bound', '1');

        var api = form.getAttribute('data-api') || 'handlers/newsletter-handler';
        var emailInput = form.querySelector('input[type="email"], input[name="email"]');
        var statusEl = form.querySelector('[data-newsletter-status]');
        var btn = form.querySelector('button[type="submit"], button.btn');
        var csrfInput = form.querySelector('input[name="csrf_token"]');

        function setStatus(message, type) {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = message || '';
            statusEl.classList.remove('is-error', 'is-success');
            if (type) {
                statusEl.classList.add(type === 'error' ? 'is-error' : 'is-success');
            }
        }

        function ensureCsrf() {
            if (csrfInput && csrfInput.value) {
                return Promise.resolve(csrfInput.value);
            }
            return fetch(api + '?action=csrf', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success || !data.csrf_token) {
                        throw new Error(data.message || 'Unable to start subscription.');
                    }
                    if (csrfInput) {
                        csrfInput.value = data.csrf_token;
                    }
                    return data.csrf_token;
                });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var email = (emailInput && emailInput.value || '').trim();
            if (!email) {
                setStatus('Please enter your email address.', 'error');
                if (emailInput) emailInput.focus();
                return;
            }

            var originalLabel = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Subscribing…';
            }
            setStatus('Subscribing…', null);

            ensureCsrf()
                .then(function (token) {
                    var body = new FormData(form);
                    body.set('action', 'subscribe');
                    body.set('csrf_token', token);
                    if (!body.get('source')) {
                        body.set('source', form.getAttribute('data-source') || 'footer');
                    }
                    return fetch(api, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: body
                    });
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'Subscription failed.');
                    }
                    setStatus(data.message || 'Subscribed successfully.', 'success');
                    if (emailInput) {
                        emailInput.value = '';
                    }
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Subscribed';
                    }
                    setTimeout(function () {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalLabel || 'Subscribe';
                        }
                    }, 2500);
                })
                .catch(function (err) {
                    setStatus(err.message || 'Unable to subscribe right now.', 'error');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalLabel || 'Subscribe';
                    }
                });
        });
    }

    function init() {
        document.querySelectorAll('form[data-newsletter], #agFooterNewsletter, #agNewsletterForm').forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
