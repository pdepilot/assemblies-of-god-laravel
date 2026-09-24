@php
    $programs = array_values(array_filter(
        is_array($worshipPrograms ?? null) ? $worshipPrograms : [],
        static fn ($item): bool => is_array($item) && (trim((string) ($item['title'] ?? '')) !== '' || trim((string) ($item['day'] ?? '')) !== '')
    ));
    $toHref = $toHref ?? static function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl($path);
    };
    $mapSrc = trim((string) ($worshipLocation['embed_src'] ?? ''));
    if ($mapSrc === '') {
        $mapSrc = app(\App\Services\Website\WorshipReadService::class)->location()['embed_src'];
    }
@endphp
<div id="worship" class="container-fluid worship-schedule py-5">
    <div class="container py-5">
        <div class="mx-auto text-center mb-5 wow fadeIn" data-wow-delay="0.1s" style="max-width: 700px;">
            <h1 class="display-3">Our Worship</h1>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach ($programs as $index => $program)
                @php
                    $delay = number_format(0.1 + ($index * 0.2), 1, '.', '');
                    $icon = trim((string) ($program['icon'] ?? 'fa-church')) ?: 'fa-church';
                    $ctaLabel = trim((string) ($program['cta_label'] ?? ''));
                    $ctaUrl = trim((string) ($program['cta_url'] ?? ''));
                    $href = $ctaUrl !== '' ? $toHref($ctaUrl) : '';
                @endphp
                <div class="col-lg-6 col-xl-4">
                    <div class="worship-card p-4 wow fadeIn" data-wow-delay="{{ $delay }}s">
                        @if (trim((string) ($program['day'] ?? '')) !== '')
                            <p class="worship-day mb-2"><i class="fa {{ $icon }} me-2"></i>{{ $program['day'] }}</p>
                        @endif
                        @if (trim((string) ($program['title'] ?? '')) !== '')
                            <h4 class="mb-3">{{ $program['title'] }}</h4>
                        @endif
                        @if (trim((string) ($program['time_primary'] ?? '')) !== '')
                            <p class="worship-time {{ trim((string) ($program['note_primary'] ?? '')) !== '' ? 'mb-1' : 'mb-3' }}">{{ $program['time_primary'] }}</p>
                            @if (trim((string) ($program['note_primary'] ?? '')) !== '')
                                <p class="text-muted {{ trim((string) ($program['time_secondary'] ?? '')) !== '' ? 'mb-3' : 'mb-4' }}">{{ $program['note_primary'] }}</p>
                            @endif
                        @endif
                        @if (trim((string) ($program['time_secondary'] ?? '')) !== '')
                            <p class="worship-time mb-1">{{ $program['time_secondary'] }}</p>
                            @if (trim((string) ($program['note_secondary'] ?? '')) !== '')
                                <p class="text-muted mb-4">{{ $program['note_secondary'] }}</p>
                            @endif
                        @endif
                        @if (trim((string) ($program['body'] ?? '')) !== '')
                            <p class="{{ $ctaLabel !== '' && $href !== '' ? 'mb-4' : 'mb-0' }}">{{ $program['body'] }}</p>
                        @endif
                        @if ($ctaLabel !== '' && $href !== '')
                            <a href="{{ $href }}" class="btn btn-primary px-3">{{ $ctaLabel }}</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row mt-5 wow fadeIn" data-wow-delay="0.2s">
            <div class="col-12">
                <div class="ag-location-map">
                    <div class="row g-0">
                        <div class="col-lg-7 ag-location-map__embed">
                            <iframe
                                title="{{ ($church['church_name'] ?? config('identity.public.site_name', 'Assemblies of God Church Ikenegbu')).' location on Google Maps' }}"
                                src="{{ $mapSrc }}"
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
