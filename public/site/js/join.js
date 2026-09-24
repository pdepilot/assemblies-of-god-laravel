(function () {
    'use strict';

    var API = window.JOIN_API || '';
    var form = document.getElementById('joinMemberForm');
    var success = document.getElementById('joinSuccess');
    if (!form) return;

    function $(id) { return document.getElementById(id); }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function ageFromDob(dob) {
        if (!dob) return null;
        var parts = dob.split('-');
        if (parts.length !== 3) return null;
        var birth = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(birth.getTime())) return null;
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        return age >= 0 ? age : null;
    }

    function ministryLabel(age, gender, marital) {
        gender = String(gender || '').toLowerCase();
        marital = String(marital || '').toLowerCase();
        if (age === null) return null;
        if (age < 13) return 'Children Ministry';
        if (age <= 19) return 'Teen Ministry';
        if (marital === 'single') return 'Youth Ministry';
        if (marital === 'widow' || (marital === 'widowed' && gender === 'female')) return 'Widows';
        if (marital === 'widower' || (marital === 'widowed' && gender === 'male')) return "Men's Ministry";
        if (marital === 'married' || marital === 'divorced' || marital === 'separated') {
            if (gender === 'male') return "Men's Ministry";
            if (gender === 'female') return "Women's Ministry";
        }
        return null;
    }

    function syncWedding() {
        var marital = $('joinMarital') ? $('joinMarital').value : '';
        var wrap = $('joinWeddingWrap');
        var input = $('joinWeddingDate');
        var show = marital === 'married';
        if (wrap) wrap.hidden = !show;
        if (input) {
            input.required = show;
            if (!show) input.value = '';
        }
        if (marital === 'widow' && $('joinGender')) $('joinGender').value = 'female';
        if (marital === 'widower' && $('joinGender')) $('joinGender').value = 'male';
        syncMinistry();
    }

    function syncMinistry() {
        var el = $('joinMinistryPreview');
        if (!el) return;
        var age = ageFromDob($('joinDob') ? $('joinDob').value : '');
        var gender = $('joinGender') ? $('joinGender').value : '';
        var marital = $('joinMarital') ? $('joinMarital').value : '';
        var label = ministryLabel(age, gender, marital);
        if (label) {
            el.textContent = 'You will be enrolled in: ' + label;
        } else if (age !== null && age >= 20) {
            el.textContent = 'Select marital status (and gender where needed) to see your ministry.';
        } else {
            el.textContent = 'Ministry placement will appear after you enter date of birth, gender, and marital status.';
        }
    }

    function setStatus(msg, ok) {
        var el = $('joinStatus');
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('is-error', !ok);
        el.classList.toggle('is-success', !!ok);
    }

    if ($('joinDob')) $('joinDob').addEventListener('change', syncMinistry);
    if ($('joinGender')) $('joinGender').addEventListener('change', syncMinistry);
    if ($('joinMarital')) $('joinMarital').addEventListener('change', syncWedding);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = $('joinSubmitBtn');
        if (btn) btn.disabled = true;
        setStatus('Submitting…', true);

        var payload = {
            csrf_token: csrf(),
            first_name: ($('joinFirstName') && $('joinFirstName').value) || '',
            last_name: ($('joinLastName') && $('joinLastName').value) || '',
            phone: ($('joinPhone') && $('joinPhone').value) || '',
            email: ($('joinEmail') && $('joinEmail').value) || '',
            date_of_birth: ($('joinDob') && $('joinDob').value) || '',
            gender: ($('joinGender') && $('joinGender').value) || '',
            marital_status: ($('joinMarital') && $('joinMarital').value) || '',
            wedding_date: ($('joinWeddingDate') && $('joinWeddingDate').value) || '',
            address_line1: ($('joinAddress') && $('joinAddress').value) || '',
            city: ($('joinCity') && $('joinCity').value) || '',
            state: ($('joinState') && $('joinState').value) || '',
            country: 'Nigeria',
            ag_hp_trap: (form.querySelector('[name="ag_hp_trap"]') || {}).value || ''
        };

        fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (d) {
                    return { ok: r.ok, d: d };
                });
            })
            .then(function (res) {
                var d = res.d || {};
                if (!d.success) throw new Error(d.message || 'Registration failed.');
                form.hidden = true;
                if (success) {
                    success.hidden = false;
                    if ($('joinSuccessMsg')) {
                        $('joinSuccessMsg').textContent = d.message || 'Welcome to the AGC Ikenegbu family.';
                    }
                    if ($('joinSuccessCode')) {
                        $('joinSuccessCode').textContent = (d.member && d.member.member_code) || '—';
                    }
                    if ($('joinSuccessMinistry')) {
                        $('joinSuccessMinistry').textContent = (d.member && d.member.ministry_label) || '—';
                    }
                }
                setStatus('', true);
            })
            .catch(function (err) {
                setStatus(err.message || 'Registration failed.', false);
            })
            .finally(function () {
                if (btn) btn.disabled = false;
            });
    });

    syncWedding();
})();
