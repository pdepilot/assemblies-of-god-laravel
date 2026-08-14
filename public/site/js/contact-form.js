/**
 * AGC Ikenegbu — public contact form submission
 */
(function () {
    'use strict';

    var form = document.querySelector('.contact-form');
    if (!form) return;

    var tabs = document.querySelectorAll('.contact-type-tab');
    var subject = document.getElementById('contactSubject');
    var typeInput = document.getElementById('contactInquiryType');
    var alertBox = document.getElementById('contactFormAlert');
    var submitBtn = form.querySelector('button[type="submit"]');

    var placeholders = {
        general: 'How can we help you?',
        prayer: 'Prayer request topic',
        visit: 'Preferred visit date / questions'
    };

    var activeType = 'general';
    var apiBase = form.getAttribute('data-api') || '/api/contact';
    if (apiBase.indexOf('handlers/contact-handler') !== -1) {
        apiBase = '/api/contact';
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
        if (!submitBtn) return;
        submitBtn.disabled = loading;
        submitBtn.innerHTML = loading
            ? '<i class="fa fa-spinner fa-spin me-2"></i>Sending…'
            : '<i class="fa fa-paper-plane me-2"></i>Send Message';
    }

    function setActiveType(type) {
        activeType = type;
        if (typeInput) typeInput.value = type;
        tabs.forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.type === type);
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

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideAlert();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        setLoading(true);

        var body = new FormData(form);
        body.append('action', 'submit');
        body.append('inquiry_type', activeType);

        fetch(apiBase, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Unable to send your message.');
                    }
                    return data;
                });
            })
            .then(function (res) {
                form.reset();
                setActiveType('general');
                showAlert(
                    '<strong>Thank you!</strong> Your message has been received. Reference: <code>' +
                    (res.reference || '') + '</code>. Our team will respond with care.',
                    'success'
                );
            })
            .catch(function (err) {
                showAlert(escapeHtml(err.message || 'Something went wrong. Please try again or call the church office.'), 'error');
            })
            .finally(function () {
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
})();
