<form
    id="cmsContactForm"
    class="contact-form"
    novalidate
    data-api="{{ route('public.contact.submit') }}"
    data-submit-url="{{ route('public.contact.submit') }}"
>
    @csrf
    <input type="hidden" name="action" value="submit">
    <input type="hidden" name="inquiry_type" id="contactInquiryType" value="general">
    <input type="text" name="ag_hp_trap" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="ag-hp-trap">

    <div class="contact-type-tabs" role="tablist" aria-label="Message type">
        <button type="button" class="contact-type-tab active" data-type="general" role="tab" aria-selected="true">General</button>
        <button type="button" class="contact-type-tab" data-type="prayer" role="tab" aria-selected="false">Prayer request</button>
        <button type="button" class="contact-type-tab" data-type="visit" role="tab" aria-selected="false">Plan a visit</button>
    </div>

    <div id="contactFormAlert" class="contact-form-alert is-hidden" role="alert"></div>
    <div id="cmsContactAlert" class="contact-form-alert is-hidden" role="alert"></div>

    <div class="row g-0">
        <div class="col-md-6">
            <div class="contact-field">
                <span class="field-icon" aria-hidden="true"><i class="fa fa-user"></i></span>
                <label class="visually-hidden" for="cmsContactName">Full name</label>
                <input type="text" class="form-control" id="cmsContactName" name="name" placeholder="Full name" autocomplete="name" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="contact-field">
                <span class="field-icon" aria-hidden="true"><i class="fa fa-envelope"></i></span>
                <label class="visually-hidden" for="cmsContactEmail">Email</label>
                <input type="email" class="form-control" id="cmsContactEmail" name="email" placeholder="Email address" autocomplete="email" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="contact-field">
                <span class="field-icon" aria-hidden="true"><i class="fa fa-phone"></i></span>
                <label class="visually-hidden" for="cmsContactPhone">Phone</label>
                <input type="tel" class="form-control" id="cmsContactPhone" name="phone" placeholder="Phone (optional)" autocomplete="tel">
            </div>
        </div>
        <div class="col-md-6">
            <div class="contact-field">
                <span class="field-icon" aria-hidden="true"><i class="fa fa-bookmark"></i></span>
                <label class="visually-hidden" for="contactSubject">Subject</label>
                <input type="text" class="form-control" id="contactSubject" name="subject" placeholder="How can we help you?" required>
            </div>
        </div>
        <div class="col-12">
            <div class="contact-field">
                <span class="field-icon field-icon--textarea" aria-hidden="true"><i class="fa fa-comment-alt"></i></span>
                <label class="visually-hidden" for="cmsContactMessage">Message</label>
                <textarea class="form-control" id="cmsContactMessage" name="message" rows="5" placeholder="Write your message…" required></textarea>
            </div>
        </div>
        <div class="col-12 contact-submit-wrap">
            <button type="submit" class="btn btn-primary" id="cmsContactSubmit">
                <i class="fa fa-paper-plane me-2"></i>Send Message
            </button>
        </div>
    </div>
</form>
