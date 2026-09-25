@php
    $heading = trim((string) ($page['heading'] ?? '')) ?: 'Contact Us';
    $eyebrow = trim((string) ($page['eyebrow'] ?? '')) ?: 'We Are Here';
    $intro = trim((string) ($page['intro'] ?? '')) ?: 'Reach the church office, send a prayer request, or plan your visit. We will respond with care.';
    $body = (string) ($page['body_html'] ?? '');
    $heroUrl = $page['hero_image_url'] ?? null;
    $church = is_array($church ?? null) ? $church : [];
    $mapSrc = trim((string) ($worshipLocation['embed_src'] ?? ''));
    $address = trim((string) ($church['address_full'] ?? 'AGC Ikenegbu, Ikenegbu Layout, Owerri, Imo State'));
    $phoneDisplay = trim((string) ($church['phone_display'] ?? ''));
    $phoneTel = trim((string) ($church['phone_tel'] ?? $church['phone'] ?? ''));
    $email = trim((string) ($church['email'] ?? ''));
    $sunday = trim((string) ($church['sunday_worship'] ?? ''));
    $midweek = trim((string) ($church['midweek_service'] ?? ''));
    $prayer = trim((string) ($church['prayer_meeting'] ?? ''));
@endphp
@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'contact'])

@include('public.partials.page-hero', [
    'heroTitle' => $heading,
    'breadcrumbCurrent' => $heading,
    'heroUrl' => $heroUrl,
])

<section class="container-fluid contact-section py-5">
    <div class="container py-4">
        <div class="contact-section-header text-center mx-auto mb-5" style="max-width:720px">
            <span class="contact-badge">{{ $eyebrow }}</span>
            <h2 class="display-6 mb-3">Let’s connect</h2>
            <p class="lead mb-0">{{ $intro }}</p>
        </div>

        @if (trim(strip_tags($body)) !== '')
            <div class="cms-page-body contact-cms-copy mx-auto mb-5">{!! $body !!}</div>
        @endif

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="contact-info-panel h-100">
                    <aside class="contact-info-card">
                        <h3>Visit &amp; reach us</h3>
                        <div class="contact-info-item">
                            <div class="contact-info-icon" aria-hidden="true"><i class="fa fa-map-marker-alt"></i></div>
                            <div>
                                <strong>Church location</strong>
                                <p>{{ $address }}</p>
                            </div>
                        </div>
                        @if ($phoneDisplay !== '')
                            <div class="contact-info-item">
                                <div class="contact-info-icon" aria-hidden="true"><i class="fa fa-phone-alt"></i></div>
                                <div>
                                    <strong>Phone</strong>
                                    <a href="tel:{{ $phoneTel }}">{{ $phoneDisplay }}</a>
                                </div>
                            </div>
                        @endif
                        @if ($email !== '')
                            <div class="contact-info-item">
                                <div class="contact-info-icon" aria-hidden="true"><i class="far fa-envelope"></i></div>
                                <div>
                                    <strong>Email</strong>
                                    <a href="mailto:{{ $email }}">{{ $email }}</a>
                                </div>
                            </div>
                        @endif
                        @if ($sunday !== '' || $midweek !== '' || $prayer !== '')
                            <div class="contact-info-item">
                                <div class="contact-info-icon" aria-hidden="true"><i class="fa fa-clock"></i></div>
                                <div>
                                    <strong>Gathering times</strong>
                                    @if ($sunday !== '')
                                        <p>Sunday — {{ $sunday }}</p>
                                    @endif
                                    @if ($midweek !== '')
                                        <p>{{ $midweek }}</p>
                                    @endif
                                    @if ($prayer !== '')
                                        <p>{{ $prayer }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <a
                            class="btn btn-primary contact-directions-btn mt-3"
                            href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($address) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="fa fa-location-arrow me-2"></i>Get directions
                        </a>
                        <div class="contact-prayer-banner">
                            <p><strong>Need prayer?</strong> Choose Prayer request on the form. Our team will stand with you in faith.</p>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="contact-form-card h-100">
                    <h3>Send a message</h3>
                    <p class="form-subtitle">Tell us how we can help. We typically reply from the church office.</p>
                    @include('public.partials.contact-form')
                </div>
            </div>
        </div>

        @if ($mapSrc !== '')
            <div class="contact-map-wrap mt-5">
                <iframe
                    title="{{ ($church['church_name'] ?? 'AGC Ikenegbu').' location on Google Maps' }}"
                    src="{{ $mapSrc }}"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        @endif
    </div>
</section>

@include('public.partials.footer')

<div id="contactSuccessPopup" class="contact-success-popup" hidden>
    <div class="contact-success-popup__backdrop" data-close-success></div>
    <div class="contact-success-popup__card" role="dialog" aria-modal="true" aria-labelledby="contactSuccessTitle">
        <div class="contact-success-popup__icon" aria-hidden="true"><i class="fa fa-check"></i></div>
        <h3 id="contactSuccessTitle">Message sent</h3>
        <p>Thank you. Your message was received successfully. Our team will respond with care.</p>
        <p class="contact-success-popup__ref" id="contactSuccessRef" hidden></p>
        <button type="button" class="btn btn-primary" data-close-success>OK</button>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('site/js/contact-form.js') }}?v={{ is_file(public_path('site/js/contact-form.js')) ? filemtime(public_path('site/js/contact-form.js')) : time() }}" defer></script>
@endpush
