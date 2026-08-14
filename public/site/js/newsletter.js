/**
 * AG footer / site newsletter subscribe (Laravel endpoint)
 */
(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function bindForm(form) {
        if (!form || form.getAttribute('data-newsletter-bound') === '1') {
            return;
        }
        form.setAttribute('data-newsletter-bound', '1');

        var api = form.getAttribute('data-api') || '/api/newsletter/subscribe';
        var emailInput = form.querySelector('input[type="email"], input[name="email"]');
        var statusEl = form.querySelector('[data-newsletter-status]');
        var btn = form.querySelector('button[type="submit"], button.btn');

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

            var body = new FormData(form);
            if (!body.get('source')) {
                body.set('source', form.getAttribute('data-source') || 'footer');
            }

            fetch(api, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.success) {
                            var msg = data.message;
                            if (!msg && data.errors && data.errors.email) {
                                msg = data.errors.email[0];
                            }
                            throw new Error(msg || 'Subscription failed.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
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
