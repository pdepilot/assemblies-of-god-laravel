@php
    $banner = is_array($about['scripture_banner'] ?? null) ? $about['scripture_banner'] : [];
    $verses = app(\App\Services\PublicSite\BibleVerseReadService::class)->randomVerses(10);
    $ctaLabel = trim((string) ($banner['cta_label'] ?? 'Learn More'));
    $ctaUrl = trim((string) ($banner['cta_url'] ?? 'about'));
    $toHref = function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl($path);
    };
@endphp
@if ($verses !== [])
    <div class="container-fluid pb-5">
        <div class="container text-center bg-primary py-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="row g-4 align-items-center">
                <div class="col-lg-2">
                    <i class="fa fa-church fa-5x text-dark"></i>
                </div>
                <div class="col-lg-7 text-center text-lg-start">
                    <div class="ag-scripture-rotator" id="agScriptureRotator" aria-live="polite">
                        <div class="ag-scripture-track">
                            @foreach ($verses as $i => $verse)
                                <blockquote class="ag-scripture-card{{ $i === 0 ? ' active' : '' }}">
                                    <p class="ag-scripture-quote">&ldquo;{{ $verse['quote'] }}&rdquo;</p>
                                    <cite class="ag-scripture-ref">{{ $verse['reference'] ?? '' }}</cite>
                                </blockquote>
                            @endforeach
                        </div>
                        <div class="ag-scripture-progress" aria-hidden="true">
                            <span class="ag-scripture-progress-fill"></span>
                        </div>
                    </div>
                </div>
                @if ($ctaLabel !== '')
                    <div class="col-lg-3">
                        <a href="{{ $toHref($ctaUrl !== '' ? $ctaUrl : 'about') }}" class="btn btn-dark py-2 px-4">{{ $ctaLabel }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
