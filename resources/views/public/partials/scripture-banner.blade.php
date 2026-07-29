@php
    $banner = is_array($about['scripture_banner'] ?? null) ? $about['scripture_banner'] : [];
    $verses = array_values(array_filter(
        is_array($banner['verses'] ?? null) ? $banner['verses'] : [],
        static fn ($v): bool => is_array($v) && trim((string) ($v['quote'] ?? '')) !== ''
    ));
    $ctaLabel = trim((string) ($banner['cta_label'] ?? ''));
    $ctaUrl = trim((string) ($banner['cta_url'] ?? 'about'));
    $toHref = function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl($path);
    };
@endphp
@if ($verses !== [])
    <div class="container-fluid py-5 ag-scripture-banner" style="background:linear-gradient(135deg,#1A2B5C 0%,#243a73 100%)">
        <div class="container py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-9">
                    @php $verse = $verses[array_rand($verses)]; @endphp
                    <p class="fs-5 text-uppercase text-white-50 mb-2">Scripture</p>
                    <blockquote class="blockquote text-white mb-2">
                        <p class="mb-2 fs-4">“{{ $verse['quote'] }}”</p>
                        <footer class="blockquote-footer text-white-50">{{ $verse['reference'] ?? '' }}</footer>
                    </blockquote>
                </div>
                @if ($ctaLabel !== '')
                    <div class="col-lg-3 text-lg-end">
                        <a href="{{ $toHref($ctaUrl !== '' ? $ctaUrl : 'about') }}" class="btn btn-light py-3 px-4">{{ $ctaLabel }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
