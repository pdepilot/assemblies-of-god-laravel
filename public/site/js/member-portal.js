(function () {
    'use strict';

    var API = window.MEMBER_PORTAL_API || 'api';
    var charts = {};
    var dashboardData = null;
    var activeTab = 'overview';

    function $(id) { return document.getElementById(id); }

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function money(n) {
        try {
            return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', maximumFractionDigits: 0 }).format(Number(n || 0));
        } catch (e) {
            return '₦' + Number(n || 0).toLocaleString();
        }
    }

    function esc(v) {
        if (v == null) return '';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function setStatus(el, msg, ok) {
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('is-error', !ok);
        el.classList.toggle('is-success', !!ok);
    }

    function showAuthTab(tab) {
        var loginPanel = $('mpAuthLoginPanel');
        var joinPanel = $('mpAuthJoinPanel');
        document.querySelectorAll('[data-mp-auth-tab]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-mp-auth-tab') === tab);
        });
        if (loginPanel) loginPanel.hidden = tab !== 'login';
        if (joinPanel) joinPanel.hidden = tab !== 'join';
        try {
            var url = new URL(window.location.href);
            if (tab === 'join') url.searchParams.set('mode', 'join');
            else url.searchParams.delete('mode');
            history.replaceState(history.state, '', url.pathname + url.search + url.hash);
        } catch (e) {}
    }

    function calcAgeFromDob(dob) {
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

    function ministryKeyFromProfile(age, gender, marital) {
        if (age === null) return null;
        gender = String(gender || 'unspecified').toLowerCase();
        marital = String(marital || 'unspecified').toLowerCase();
        if (age < 13) return 'children';
        if (age <= 19) return 'teens';
        if (marital === 'single') return 'youths';
        if (marital === 'widow' || (marital === 'widowed' && gender === 'female')) return 'widows';
        if (marital === 'widower' || (marital === 'widowed' && gender === 'male')) return 'men';
        if (marital === 'married' || marital === 'divorced' || marital === 'separated') {
            if (gender === 'male') return 'men';
            if (gender === 'female') return 'women';
        }
        return null;
    }

    function ministryLabel(age, gender, marital) {
        var key = ministryKeyFromProfile(age, gender, marital);
        var labels = {
            children: 'Children Ministry',
            teens: 'Teen Ministry',
            youths: 'Youth Ministry',
            men: "Men's Ministry",
            women: "Women's Ministry",
            widows: 'Widows'
        };
        if (!key) {
            if (age === null) return '—';
            if (age >= 20) return 'Set marital status / gender';
            return '—';
        }
        return labels[key] || key;
    }

    function suggestDepartmentForMinistry(key) {
        var map = {
            children: ['Children Ministry', 'Children'],
            teens: ['Teen Ministry'],
            youths: ['Youth Ministry', 'Youth'],
            men: ["Men's Ministry"],
            women: ["Women's Ministry"],
            widows: ['Widows']
        };
        return map[key] || [];
    }

    var AUTO_MINISTRY_DEPARTMENTS = [
        'Children', 'Children Ministry', 'Teen Ministry', 'Youth', 'Youth Ministry',
        "Men's Ministry", "Women's Ministry", 'Widows', 'Member'
    ];

    function syncParentGuardianSection(age) {
        var section = $('parentGuardianFields');
        if (!section) return;
        section.hidden = !(age !== null && age <= 19);
    }

    function syncJoinAgeFields() {
        var dob = $('memberDob') ? $('memberDob').value : '';
        var age = calcAgeFromDob(dob);
        var gender = $('memberGender') ? $('memberGender').value : 'unspecified';
        var marital = $('memberMaritalStatus') ? $('memberMaritalStatus').value : 'unspecified';
        if ($('memberAgeDisplay')) $('memberAgeDisplay').value = age !== null ? String(age) + ' years' : '';
        if ($('memberMinistry')) $('memberMinistry').value = ministryLabel(age, gender, marital);
        syncParentGuardianSection(age);

        var key = ministryKeyFromProfile(age, gender, marital);
        var deptSelect = $('memberDepartment');
        if (!deptSelect || !key) return;
        var suggestions = suggestDepartmentForMinistry(key);
        var current = deptSelect.value;
        var currentIsAuto = !current || AUTO_MINISTRY_DEPARTMENTS.indexOf(current) !== -1;
        if (!currentIsAuto) return;
        for (var i = 0; i < suggestions.length; i++) {
            for (var j = 0; j < deptSelect.options.length; j++) {
                if (deptSelect.options[j].value === suggestions[i]) {
                    deptSelect.value = suggestions[i];
                    return;
                }
            }
        }
    }

    function syncJoinWedding() {
        var marital = $('memberMaritalStatus') ? $('memberMaritalStatus').value : '';
        var isMarried = marital === 'married';
        var dateField = $('weddingDateField');
        var yearField = $('weddingYearField');
        if (dateField) dateField.hidden = !isMarried;
        if (yearField) yearField.hidden = !isMarried;
        if (!isMarried) {
            if ($('memberWeddingDate')) {
                $('memberWeddingDate').value = '';
                $('memberWeddingDate').required = false;
            }
            if ($('memberWeddingYear')) $('memberWeddingYear').value = '';
        } else if ($('memberWeddingDate')) {
            $('memberWeddingDate').required = true;
            var v = String($('memberWeddingDate').value || '');
            if ($('memberWeddingYear')) $('memberWeddingYear').value = v.length >= 4 ? v.slice(0, 4) : '';
        }
        if ($('memberGender')) {
            if (marital === 'widow') $('memberGender').value = 'female';
            if (marital === 'widower') $('memberGender').value = 'male';
        }
        syncJoinAgeFields();
    }

    function uniqueDepartmentSelect(sel) {
        if (!sel) return;
        var seen = {};
        var keep = [];
        Array.prototype.forEach.call(sel.options, function (opt) {
            var value = String(opt.value || '').trim();
            var label = String(opt.text || '').trim();
            var key = (value || label).toLowerCase()
                .replace(/['’`]/g, '')
                .replace(/-/g, ' ')
                .replace(/\s+/g, ' ')
                .replace(/\s+(ministry|department|team|chain|group|unit)$/i, '')
                .trim();
            if (!value) {
                keep.push({ value: value, text: label });
                return;
            }
            if (seen[key]) return;
            seen[key] = true;
            keep.push({ value: value, text: label });
        });
        var current = sel.value;
        sel.innerHTML = '';
        keep.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.text;
            sel.appendChild(option);
        });
        if (current) sel.value = current;
    }

    function initJoinForm() {
        var form = $('memberForm');
        if (!form || !$('mpAuthJoinPanel')) return;
        uniqueDepartmentSelect($('memberDepartment'));

        if ($('memberDob')) $('memberDob').addEventListener('change', syncJoinAgeFields);
        if ($('memberGender')) $('memberGender').addEventListener('change', syncJoinAgeFields);
        if ($('memberMaritalStatus')) $('memberMaritalStatus').addEventListener('change', syncJoinWedding);
        if ($('memberWeddingDate')) {
            $('memberWeddingDate').addEventListener('change', function () {
                var v = String($('memberWeddingDate').value || '');
                if ($('memberWeddingYear')) $('memberWeddingYear').value = v.length >= 4 ? v.slice(0, 4) : '';
            });
        }

        var photoInput = $('memberPhoto');
        var preview = $('photoPreview');
        var removeBtn = $('btnRemovePhoto');
        var faceVerified = false;

        if (typeof window.initCmsPhotoUpload === 'function' && photoInput && preview) {
            window.initCmsPhotoUpload({
                previewId: 'photoPreview',
                inputId: 'memberPhoto',
                dropId: 'photoDrop',
                removeBtnId: 'btnRemovePhoto',
                cameraBtnId: 'btnMemberCamera',
                requireFace: true,
                onSelect: function () {
                    faceVerified = true;
                    if ($('photoDrop')) $('photoDrop').classList.remove('is-required-error');
                    setStatus($('mpJoinStatus'), 'Face verified — photo accepted.', true);
                },
                onClear: function () {
                    faceVerified = false;
                },
                onFaceAccepted: function () {
                    faceVerified = true;
                    if ($('photoDrop')) $('photoDrop').classList.remove('is-required-error');
                },
                onFaceRejected: function () {
                    faceVerified = false;
                    if ($('photoDrop')) $('photoDrop').classList.add('is-required-error');
                },
                toast: function (msg) {
                    var checking = /checking for a face/i.test(String(msg || ''));
                    setStatus($('mpJoinStatus'), msg, checking);
                }
            });
        } else if (photoInput && preview) {
            photoInput.addEventListener('change', function () {
                var file = photoInput.files && photoInput.files[0];
                if (!file) return;
                if (!window.AGFaceDetect) {
                    setStatus($('mpJoinStatus'), 'Face verification is unavailable. Please refresh the page.', false);
                    photoInput.value = '';
                    return;
                }
                setStatus($('mpJoinStatus'), 'Checking for a face…', true);
                window.AGFaceDetect.validateFacialPhoto(file).then(function (result) {
                    if (!result || !result.ok) {
                        faceVerified = false;
                        photoInput.value = '';
                        preview.innerHTML = '<div class="cms-photo-upload__placeholder"><i class="fas fa-user"></i><span>Photo preview</span></div>';
                        if (removeBtn) removeBtn.style.display = 'none';
                        if ($('photoDrop')) $('photoDrop').classList.add('is-required-error');
                        setStatus($('mpJoinStatus'), (result && result.message) || window.AGFaceDetect.FACE_MSG, false);
                        return;
                    }
                    faceVerified = true;
                    var url = URL.createObjectURL(file);
                    preview.innerHTML = '<img src="' + url + '" alt="Photo preview">';
                    if (removeBtn) removeBtn.style.display = '';
                    if ($('photoDrop')) $('photoDrop').classList.remove('is-required-error');
                    setStatus($('mpJoinStatus'), 'Face verified — photo accepted.', true);
                });
            });
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    faceVerified = false;
                    photoInput.value = '';
                    preview.innerHTML = '<div class="cms-photo-upload__placeholder"><i class="fas fa-user"></i><span>Photo preview</span></div>';
                    removeBtn.style.display = 'none';
                });
            }
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = $('mpJoinBtn');
            var status = $('mpJoinStatus');
            var selectedPhoto = photoInput && photoInput.files && photoInput.files[0];
            if (!selectedPhoto || !faceVerified) {
                setStatus(status, 'A clear facial photo is required. Please upload or capture a solo portrait of your face.', false);
                if ($('photoDrop')) {
                    $('photoDrop').classList.add('is-required-error');
                    $('photoDrop').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            if ($('photoDrop')) $('photoDrop').classList.remove('is-required-error');
            var fd = new FormData(form);
            fd.append('csrf_token', csrf());
            if (btn) btn.disabled = true;
            setStatus(status, 'Submitting…', true);

            fetch(window.MEMBER_JOIN_API || (API + '/member-self-register.php'), {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(function (r) {
                    return r.json().then(function (d) {
                        return { d: d };
                    });
                })
                .then(function (res) {
                    var d = res.d || {};
                    if (!d.success) throw new Error(d.message || 'Registration failed.');
                    form.hidden = true;
                    var success = $('mpJoinSuccess');
                    if (success) {
                        success.hidden = false;
                        if ($('mpJoinSuccessMsg')) $('mpJoinSuccessMsg').textContent = d.message || 'Welcome!';
                        if ($('mpJoinSuccessCode')) $('mpJoinSuccessCode').textContent = (d.member && d.member.member_code) || '—';
                        if ($('mpJoinSuccessMinistry')) {
                            $('mpJoinSuccessMinistry').textContent = (d.member && d.member.ministry_label) || '—';
                        }
                    }
                    if (d.member && d.member.phone && $('mpIdentifier')) {
                        $('mpIdentifier').value = d.member.phone;
                    }
                    setStatus(status, '', true);
                })
                .catch(function (err) {
                    setStatus(status, err.message || 'Registration failed.', false);
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                });
        });

        if ($('mpJoinGoLoginBtn')) {
            $('mpJoinGoLoginBtn').addEventListener('click', function () {
                showAuthTab('login');
                if ($('mpPassword') && $('memberPhone') && $('memberPhone').value) {
                    $('mpPassword').value = $('memberPhone').value;
                }
                if ($('mpIdentifier')) $('mpIdentifier').focus();
            });
        }

        syncJoinWedding();
    }

    function initAuthTabs() {
        document.querySelectorAll('[data-mp-auth-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showAuthTab(btn.getAttribute('data-mp-auth-tab') || 'login');
            });
        });
        var initial = (document.body.getAttribute('data-mp-mode') || 'login');
        if (initial === 'join') showAuthTab('join');
        else if (initial === 'setup') {
            showAuthTab('login');
            if ($('memberPortalLoginForm')) $('memberPortalLoginForm').hidden = true;
            if ($('memberPortalSetupForm')) $('memberPortalSetupForm').hidden = false;
        }
    }

    function initLogin() {
        var form = $('memberPortalLoginForm');
        var setupForm = $('memberPortalSetupForm');
        if (!form && !setupForm && !$('mpAuthJoinPanel')) return;

        initAuthTabs();
        initJoinForm();

        function showSetup(prefill) {
            if (form) form.hidden = true;
            if (setupForm) setupForm.hidden = false;
            if (prefill && $('mpSetupIdentifier')) {
                $('mpSetupIdentifier').value = prefill;
            } else if ($('mpIdentifier') && $('mpSetupIdentifier') && !$('mpSetupIdentifier').value) {
                $('mpSetupIdentifier').value = $('mpIdentifier').value || '';
            }
            if ($('mpSetupIdentifier')) $('mpSetupIdentifier').focus();
        }

        function showLogin() {
            if (setupForm) setupForm.hidden = true;
            if (form) form.hidden = false;
            if ($('mpIdentifier')) $('mpIdentifier').focus();
        }

        if ($('mpShowSetupBtn')) {
            $('mpShowSetupBtn').addEventListener('click', function () {
                showSetup(($('mpIdentifier') && $('mpIdentifier').value) || '');
            });
        }
        if ($('mpShowLoginBtn')) {
            $('mpShowLoginBtn').addEventListener('click', showLogin);
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = $('mpLoginBtn');
                var status = $('mpLoginStatus');
                var fd = new FormData(form);
                fd.append('csrf_token', csrf());
                if (btn) btn.disabled = true;
                setStatus(status, 'Signing in…', true);

                fetch(API + '/member-portal-login.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) {
                        return r.json().then(function (d) {
                            return { ok: r.ok, status: r.status, d: d };
                        }).catch(function () {
                            throw new Error('Unexpected server response (' + r.status + '). Please refresh and try again.');
                        });
                    })
                    .then(function (res) {
                        var d = res.d || {};
                        if (d.needs_password_setup) {
                            setStatus(status, d.message || 'No password yet — set one to continue.', true);
                            showSetup(fd.get('identifier') || '');
                            return;
                        }
                        if (!d.success) throw new Error(d.message || 'Login failed.');
                        setStatus(status, d.message || 'Success!', true);
                        window.location.href = window.MEMBER_PORTAL_DASH || 'member-portal/';
                    })
                    .catch(function (err) {
                        setStatus(status, err.message || 'Login failed.', false);
                    })
                    .finally(function () {
                        if (btn) btn.disabled = false;
                    });
            });
        }

        if (setupForm) {
            setupForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = $('mpSetupBtn');
                var status = $('mpSetupStatus');
                var password = ($('mpNewPassword') && $('mpNewPassword').value) || '';
                var confirm = ($('mpNewPasswordConfirm') && $('mpNewPasswordConfirm').value) || '';
                if (password !== confirm) {
                    setStatus(status, 'Password confirmation does not match.', false);
                    return;
                }
                var fd = new FormData(setupForm);
                fd.append('csrf_token', csrf());
                if (btn) btn.disabled = true;
                setStatus(status, 'Setting password…', true);

                fetch(API + '/member-portal-set-password.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d.success) throw new Error(d.message || 'Unable to set password.');
                        setStatus(status, d.message || 'Password set!', true);
                        window.location.href = window.MEMBER_PORTAL_DASH || 'member-portal/';
                    })
                    .catch(function (err) {
                        setStatus(status, err.message || 'Unable to set password.', false);
                    })
                    .finally(function () {
                        if (btn) btn.disabled = false;
                    });
            });

            if ($('mpSetupIdentifier')) {
                var setupTimer = null;
                $('mpSetupIdentifier').addEventListener('blur', function () {
                    clearTimeout(setupTimer);
                    setupTimer = setTimeout(function () {
                        var identifier = ($('mpSetupIdentifier').value || '').trim();
                        if (identifier.length < 3) return;
                        var fd = new FormData();
                        fd.append('csrf_token', csrf());
                        fd.append('identifier', identifier);
                        fetch(API + '/member-portal-check-setup.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                if (!d.success || !d.data) return;
                                if (d.data.needs_password_setup && d.data.confirm_hint && $('mpConfirmHint')) {
                                    $('mpConfirmHint').textContent = d.data.confirm_hint;
                                }
                                if (d.data.display_name && $('mpSetupHint')) {
                                    $('mpSetupHint').textContent = 'Welcome, ' + d.data.display_name +
                                        (d.data.member_code ? ' (' + d.data.member_code + ')' : '') +
                                        '. Confirm your details, then create a password.';
                                }
                            })
                            .catch(function () {});
                    }, 200);
                });
            }
        }
    }

    function fetchDashboard(type, year) {
        var q = new URLSearchParams();
        if (type && type !== 'overview') q.set('type', type);
        if (year) q.set('year', year);
        return fetch(API + '/member-portal-dashboard.php?' + q.toString(), { credentials: 'same-origin' })
            .then(function (r) {
                if (r.status === 401) {
                    window.location.href = window.MEMBER_PORTAL_LOGIN || 'member-portal/login';
                    throw new Error('Session expired.');
                }
                return r.json();
            })
            .then(function (d) {
                if (!d.success) throw new Error(d.message || 'Load failed.');
                return d.data;
            });
    }

    function renderStats(summary) {
        summary = summary || {};
        if ($('mpStatLifetime')) $('mpStatLifetime').textContent = money(summary.lifetime_total);
        if ($('mpStatTithes')) $('mpStatTithes').textContent = money(summary.tithes);
        if ($('mpStatProjects')) $('mpStatProjects').textContent = money(summary.projects);
    }

    function renderTable(records, container, options) {
        options = options || {};
        if (!container) return;
        records = records || [];
        if (!records.length) {
            container.innerHTML = '<p class="member-portal-muted">No records in this view yet.</p>';
            return;
        }
        var showGiver = options.showGiver;
        if (showGiver == null) {
            var kids = (dashboardData && dashboardData.children) || [];
            showGiver = kids.length > 0 || records.some(function (r) { return !!r.is_child; });
        }
        var rows = records.map(function (r) {
            var dl = '';
            if (options.receipts && r.receipt_download) {
                dl = '<a class="member-portal-link" href="' + esc(API + '/member-portal-download-receipt.php?source=' + encodeURIComponent(r.source) + '&id=' + encodeURIComponent(r.id)) + '"><i class="fas fa-download"></i> PDF</a>';
            } else if (options.receipts && r.receipt_number) {
                dl = esc(r.receipt_number);
            } else {
                dl = r.receipt_number ? esc(r.receipt_number) : '—';
            }
            var giver = '';
            if (showGiver) {
                var label = r.giver_name || '';
                if (r.is_child && label) {
                    label += ' (child)';
                }
                giver = '<td>' + esc(label || '—') + '</td>';
            }
            return '<tr><td>' + esc(String(r.date || '').slice(0, 10)) + '</td>' + giver + '<td>' + esc(r.category_name) + '</td><td>' + money(r.amount) + '</td><td>' + esc(r.payment_method) + '</td><td>' + dl + '</td></tr>';
        }).join('');
        var giverHead = showGiver ? '<th>Giver</th>' : '';
        container.innerHTML = '<table class="member-portal-table"><thead><tr><th>Date</th>' + giverHead + '<th>Category</th><th>Amount</th><th>Method</th><th>' + (options.receipts ? 'Receipt' : 'Ref') + '</th></tr></thead><tbody>' + rows + '</tbody></table>';
    }

    function renderChildrenNote(children) {
        var stats = $('mpStats');
        var existing = $('mpChildrenNote');
        if (existing) existing.remove();
        if (!stats || !children || !children.length) return;
        var names = children.map(function (c) { return c.full_name; }).filter(Boolean).join(', ');
        var p = document.createElement('p');
        p.id = 'mpChildrenNote';
        p.className = 'member-portal-muted';
        p.textContent = 'Also showing tithes and project gifts for your children: ' + names + '.';
        stats.insertAdjacentElement('afterend', p);
    }

    function renderCharts(chartsData) {
        if (typeof Chart === 'undefined' || !chartsData) return;
        function make(id, type, labels, values, label) {
            var el = $(id);
            if (!el || !labels || !labels.length) return;
            if (charts[id]) charts[id].destroy();
            charts[id] = new Chart(el, {
                type: type,
                data: { labels: labels, datasets: [{ label: label, data: values, borderColor: '#0b5c45', backgroundColor: type === 'line' ? 'rgba(11,92,69,0.15)' : ['#0b5c45', '#b8952c', '#2563eb', '#64748b'] }] },
                options: { responsive: true, plugins: { legend: { display: type === 'doughnut' } } }
            });
        }
        make('mpChartMonthly', 'line', chartsData.monthly && chartsData.monthly.labels, chartsData.monthly && chartsData.monthly.values, 'Monthly');
        make('mpChartAnnual', 'bar', chartsData.annual && chartsData.annual.labels, chartsData.annual && chartsData.annual.values, 'Annual');
        make('mpChartTypes', 'doughnut', chartsData.types && chartsData.types.labels, chartsData.types && chartsData.types.values, 'Types');
    }

    function renderAnnualSummary(annual) {
        var el = $('mpAnnualSummary');
        if (!el) return;
        annual = annual || {};
        var keys = Object.keys(annual).sort().reverse();
        if (!keys.length) {
            el.innerHTML = '<p class="member-portal-muted">No annual totals yet.</p>';
            return;
        }
        el.innerHTML = '<table class="member-portal-table"><thead><tr><th>Year</th><th>Total Giving</th></tr></thead><tbody>' +
            keys.map(function (y) { return '<tr><td>' + esc(y) + '</td><td><strong>' + money(annual[y]) + '</strong></td></tr>'; }).join('') +
            '</tbody></table>';
    }

    function renderStatements(statements) {
        var el = $('mpStatementsList');
        if (!el) return;
        statements = statements || [];
        if (!statements.length) {
            el.innerHTML = '<p class="member-portal-muted">No saved statements yet. Generate one above.</p>';
            return;
        }
        el.innerHTML = '<table class="member-portal-table"><thead><tr><th>Year</th><th>Generated</th><th></th></tr></thead><tbody>' +
            statements.map(function (s) {
                var y = s.statement_year || s.year;
                return '<tr><td>' + esc(y) + '</td><td>' + esc(String(s.generated_at || s.created_at || '').slice(0, 10)) + '</td><td><a class="member-portal-link" href="' + esc(API + '/member-portal-download-statement.php?year=' + encodeURIComponent(y)) + '"><i class="fas fa-download"></i> Download PDF</a></td></tr>';
            }).join('') + '</tbody></table>';
    }

    function populateYearSelect(annual) {
        var sel = $('mpStatementYear');
        if (!sel) return;
        var years = Object.keys(annual || {}).map(Number).filter(function (y) { return y > 2000; });
        var current = new Date().getFullYear();
        if (years.indexOf(current) === -1) years.push(current);
        years.sort(function (a, b) { return b - a; });
        sel.innerHTML = years.map(function (y) { return '<option value="' + y + '">' + y + '</option>'; }).join('');
    }

    function showTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.member-portal-tab').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-mp-tab') === tab);
        });
        var overview = $('mpPanelOverview');
        var filtered = $('mpPanelFiltered');
        var receipts = $('mpPanelReceipts');
        var tax = $('mpPanelTax');
        if (overview) overview.hidden = tab !== 'overview';
        if (filtered) filtered.hidden = !['tithes', 'projects'].includes(tab);
        if (receipts) receipts.hidden = tab !== 'receipts';
        if (tax) tax.hidden = tab !== 'tax';

        if (!dashboardData) return;

        if (tab === 'overview') {
            renderTable((dashboardData.records || []).slice(0, 15), $('mpRecentTable'));
        } else if (['tithes', 'projects'].includes(tab)) {
            var title = tab.charAt(0).toUpperCase() + tab.slice(1);
            if ($('mpFilteredTitle')) $('mpFilteredTitle').textContent = title;
            var bucket = (dashboardData.by_type && dashboardData.by_type[tab]) || [];
            renderTable(bucket, $('mpFilteredTable'));
        } else if (tab === 'receipts') {
            renderTable(dashboardData.records || [], $('mpReceiptsTable'), { receipts: true });
        } else if (tab === 'tax') {
            renderAnnualSummary(dashboardData.annual);
            renderStatements(dashboardData.statements);
            populateYearSelect(dashboardData.annual);
        }
    }

    function initChangePassword() {
        var form = $('mpChangePasswordForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = $('mpChangePasswordBtn');
            var status = $('mpChangePasswordStatus');
            var newPass = ($('mpNewPasswordChange') && $('mpNewPasswordChange').value) || '';
            var confirm = ($('mpConfirmPasswordChange') && $('mpConfirmPasswordChange').value) || '';
            if (newPass !== confirm) {
                setStatus(status, 'New password confirmation does not match.', false);
                return;
            }
            var fd = new FormData(form);
            fd.append('csrf_token', csrf());
            if (btn) btn.disabled = true;
            setStatus(status, 'Updating password…', true);

            fetch(API + '/member-portal-change-password.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    return r.json().then(function (d) {
                        return { status: r.status, d: d };
                    });
                })
                .then(function (res) {
                    if (res.status === 401) {
                        window.location.href = window.MEMBER_PORTAL_LOGIN || 'member-portal/login';
                        return;
                    }
                    var d = res.d || {};
                    if (!d.success) throw new Error(d.message || 'Unable to change password.');
                    setStatus(status, d.message || 'Password updated.', true);
                    form.reset();
                })
                .catch(function (err) {
                    setStatus(status, err.message || 'Unable to change password.', false);
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                });
        });
    }

    function initDashboard() {
        if (!document.body.getAttribute('data-mp-logged-in')) return;

        initChangePassword();

        fetchDashboard().then(function (data) {
            dashboardData = data;
            renderStats(data.summary);
            renderChildrenNote(data.children);
            renderCharts(data.charts);
            renderTable((data.records || []).slice(0, 15), $('mpRecentTable'));
            renderAnnualSummary(data.annual);
            renderStatements(data.statements);
            populateYearSelect(data.annual);
            if (data.member && $('mpUserGreeting')) {
                $('mpUserGreeting').textContent = 'Welcome, ' + (data.member.full_name || 'Member');
            }
        }).catch(function (e) {
            alert(e.message || 'Unable to load dashboard.');
        });

        document.querySelectorAll('[data-mp-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showTab(btn.getAttribute('data-mp-tab') || 'overview');
            });
        });

        var logout = $('mpLogoutBtn');
        if (logout) {
            logout.addEventListener('click', function () {
                var fd = new FormData();
                fd.append('csrf_token', csrf());
                fetch(API + '/member-portal-logout.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .finally(function () {
                        window.location.href = window.MEMBER_PORTAL_LOGIN || 'member-portal/login';
                    });
            });
        }

        var genBtn = $('mpGenerateStatement');
        if (genBtn) {
            genBtn.addEventListener('click', function () {
                var year = ($('mpStatementYear') && $('mpStatementYear').value) || new Date().getFullYear();
                var fd = new FormData();
                fd.append('csrf_token', csrf());
                fd.append('year', year);
                genBtn.disabled = true;
                fetch(API + '/member-portal-generate-statement.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d.success) throw new Error(d.message || 'Failed.');
                        if (d.download_url) window.location.href = d.download_url;
                        return fetchDashboard();
                    })
                    .then(function (data) {
                        dashboardData = data;
                        renderStatements(data.statements);
                    })
                    .catch(function (e) { alert(e.message); })
                    .finally(function () { genBtn.disabled = false; });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initLogin();
        initDashboard();
    });
})();
