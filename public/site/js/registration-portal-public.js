(function () {
    'use strict';

    var form = document.getElementById('publicRegistrationForm');
    var success = document.getElementById('registrationSuccess');
    var countdown = document.getElementById('eventCountdown');
    var slug = document.body.getAttribute('data-portal-slug') || '';
    var apiBase = document.body.getAttribute('data-api-base') || '/api/';

    function readCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta && meta.content ? meta.content : '';
    }

    if (countdown && countdown.dataset.date) {
        var target = new Date(countdown.dataset.date + 'T00:00:00').getTime();
        function tick() {
            var diff = target - Date.now();
            if (diff <= 0) {
                countdown.textContent = 'Event has started';
                return;
            }
            var days = Math.floor(diff / 86400000);
            var hours = Math.floor((diff % 86400000) / 3600000);
            var mins = Math.floor((diff % 3600000) / 60000);
            countdown.textContent = days + 'd ' + hours + 'h ' + mins + 'm until event';
            requestAnimationFrame(tick);
        }
        tick();
    }

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(form);
        fd.append('slug', slug);
        fd.append('csrf_token', readCsrf());

        fetch(apiBase.replace(/\/?$/, '/') + 'submit-portal-registration.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': readCsrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (res) { return res.json().then(function (d) { if (!res.ok) throw new Error(d.message || 'Failed'); return d; }); })
            .then(function (res) {
                form.hidden = true;
                if (success) {
                    success.hidden = false;
                    success.innerHTML = '<h3>Thank you, ' + (res.registrant && res.registrant.full_name ? res.registrant.full_name : 'friend') + '!</h3>' +
                        '<p>Your registration number is <strong>' + (res.registrant && res.registrant.registration_number) + '</strong>.</p>' +
                        '<p>Please keep this number for your records.</p>';
                }
            })
            .catch(function (err) {
                alert(err.message || 'Registration failed.');
            });
    });
})();
