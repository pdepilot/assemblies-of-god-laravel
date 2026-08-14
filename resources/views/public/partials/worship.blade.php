@php $homePage = is_array($homePage ?? null) ? $homePage : []; @endphp
<div class="container-fluid worship-schedule py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5 wow fadeIn" data-wow-delay="0.1s" style="max-width: 720px;">
            <p class="fs-5 text-uppercase text-primary">{{ $homePage['worship_eyebrow'] ?? 'Plan Your Visit' }}</p>
            <h1 class="display-3">{{ $homePage['worship_title'] ?? 'Join Us In Worship' }}</h1>
            <p class="mb-0">{{ $homePage['worship_intro'] ?? 'Everyone is welcome. Come worship with us at '.($church['short_name'] ?? config('identity.public.short_name', 'AGC Ikenegbu')).' - Ikenegbu Layout, Owerri.' }}</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-6 col-xl-4">
                <div class="worship-card p-4 wow fadeIn" data-wow-delay="0.1s">
                    <p class="worship-day mb-2"><i class="fa fa-calendar-day me-2"></i>Every Sunday</p>
                    <h4 class="mb-3">Sunday Worship Services</h4>
                    <p class="worship-time mb-1">8:00 AM</p>
                    <p class="text-muted mb-3">First Service — Prayer &amp; Praise</p>
                    <p class="worship-time mb-1">10:30 AM</p>
                    <p class="text-muted mb-4">Main Service — Word &amp; Communion</p>
                    <p class="mb-0"><i class="fa fa-child text-primary me-2"></i>Children's church available during both services.</p>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="worship-card p-4 wow fadeIn" data-wow-delay="0.3s">
                    <p class="worship-day mb-2"><i class="fa fa-bible me-2"></i>Every Wednesday</p>
                    <h4 class="mb-3">Midweek Bible Study</h4>
                    <p class="worship-time mb-3">6:00 PM</p>
                    <p class="mb-4">In-depth teaching, small-group discussion, and fellowship for adults and youth.</p>
                    <a href="{{ route('public.blog') }}" class="btn btn-primary px-3">Bible Study Resources</a>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="worship-card p-4 wow fadeIn" data-wow-delay="0.5s">
                    <p class="worship-day mb-2"><i class="fa fa-praying-hands me-2"></i>Every Friday</p>
                    <h4 class="mb-3">Prayer &amp; Intercession</h4>
                    <p class="worship-time mb-3">6:00 PM</p>
                    <p class="mb-4">Corporate prayer for families, the church, and our nation. Submit requests anytime.</p>
                    <a href="{{ route('public.contact') }}" class="btn btn-primary px-3">Prayer Requests</a>
                </div>
            </div>
        </div>
        <div class="row mt-5 wow fadeIn" data-wow-delay="0.2s">
            <div class="col-12">
                <div class="ag-location-map">
                    <div class="row g-0">
                        <div class="col-lg-7 ag-location-map__embed">
                            <iframe
                                title="{{ ($church['church_name'] ?? config('identity.public.site_name', 'Assemblies of God Church Ikenegbu')).' location on Google Maps' }}"
                                src="https://maps.google.com/maps?q=11+Archdeacon+Dennis+Street,+Ikenegbu,+Owerri,+Imo,+Nigeria&amp;z=16&amp;ie=UTF8&amp;iwloc=&amp;output=embed"
                                width="100%"
                                height="100%"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                        <div class="col-lg-5 ag-location-map__info">
                            <h3 class="ag-location-map__title"><i class="fa fa-map-marker-alt me-2 text-primary"></i>Church Location</h3>
                            <address class="ag-location-map__address">{{ $church['address_full'] }}</address>
                            <p class="ag-location-map__contact mb-2">
                                <i class="fa fa-phone-alt text-primary me-2"></i>
                                <a href="tel:{{ $church['phone_tel'] }}">{{ $church['phone_display'] }}</a>
                            </p>
                            <p class="ag-location-map__contact mb-4">
                                <i class="far fa-envelope text-primary me-2"></i>
                                <a href="mailto:{{ $church['email'] }}">{{ $church['email'] }}</a>
                            </p>
                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($church['address_full']) }}" class="btn btn-primary px-4 py-2" target="_blank" rel="noopener noreferrer">Get Directions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
