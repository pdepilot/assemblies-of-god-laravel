<form id="cmsContactForm" class="mt-4" novalidate data-submit-url="{{ route('public.contact.submit') }}">
    <input type="hidden" name="action" value="submit">
    <input type="hidden" name="inquiry_type" value="general">
    <input type="text" name="ag_hp_trap" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="visually-hidden" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
    <div id="cmsContactAlert" class="alert d-none" role="alert"></div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="cmsContactName">Full name</label>
            <input type="text" class="form-control" id="cmsContactName" name="name" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cmsContactEmail">Email</label>
            <input type="email" class="form-control" id="cmsContactEmail" name="email" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cmsContactPhone">Phone (optional)</label>
            <input type="tel" class="form-control" id="cmsContactPhone" name="phone">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="cmsContactSubject">Subject / prayer topic</label>
            <input type="text" class="form-control" id="cmsContactSubject" name="subject" required>
        </div>
        <div class="col-12">
            <label class="form-label" for="cmsContactMessage">Message</label>
            <textarea class="form-control" id="cmsContactMessage" name="message" rows="5" required></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary py-3 px-4" id="cmsContactSubmit">Send message</button>
        </div>
    </div>
</form>
<script>
(function () {
    var form = document.getElementById('cmsContactForm');
    if (!form) return;
    var alertBox = document.getElementById('cmsContactAlert');
    var submitBtn = document.getElementById('cmsContactSubmit');
    var submitUrl = form.getAttribute('data-submit-url') || '';
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        alertBox.classList.add('d-none');
        submitBtn.disabled = true;
        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: new FormData(form)
        }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (result) {
            alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
            alertBox.classList.add(result.ok ? 'alert-success' : 'alert-danger');
            alertBox.textContent = result.data.message || (result.ok ? 'Message sent.' : 'Unable to send message.');
            if (result.ok) form.reset();
        }).catch(function () {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.textContent = 'Unable to send message. Please try again.';
        }).finally(function () {
            submitBtn.disabled = false;
        });
    });
})();
</script>
