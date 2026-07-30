@extends('layouts.public')

@php
    $toAsset = function (string $path) use ($asset_base): string {
        return app(\App\Services\PublicSite\PublicAssetResolver::class)->url($path);
    };
    $toHref = function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl($path);
    };
    $preloaderEnabled = filter_var($preloader['enabled'] ?? true, FILTER_VALIDATE_BOOL);
    $preloaderVideo = $toAsset(trim((string) ($preloader['video_url'] ?? 'videos/Create_a_cinematic_D_animatio.mp4')));
    $preloaderShowSkip = filter_var($preloader['show_skip'] ?? false, FILTER_VALIDATE_BOOL);
    $preloaderMaxWait = max(3000, min(300000, (int) ($preloader['max_wait_ms'] ?? 120000)));
    $publicIdentity = config('identity.public');
    $shortName = (string) ($church['short_name'] ?? ($publicIdentity['short_name'] ?? 'AG Ikenebgu'));
    $welcomeVideoLabel = (string) ($publicIdentity['welcome_video_label'] ?? 'AG Ikenebgu welcome video');
    $sdtgLabel = (string) ($publicIdentity['sdtg_label'] ?? 'Send Down Thy Glory');
    $iconClass = static function (string $icon): string {
        $icon = trim($icon);
        if ($icon === '') {
            return 'fa-church';
        }
        if (! str_starts_with($icon, 'fa-')) {
            $icon = 'fa-'.ltrim($icon, 'fa-');
        }

        return $icon;
    };
@endphp

@section('content')
@if ($preloaderEnabled)
    <div id="agPreloader" class="ag-preloader show" role="status" aria-live="polite" aria-label="Loading {{ $shortName }}" data-max-wait="{{ $preloaderMaxWait }}">
        <video id="agPreloaderVideo" class="ag-preloader__video" src="{{ $preloaderVideo }}" playsinline webkit-playsinline autoplay preload="auto" aria-label="{{ $welcomeVideoLabel }}"></video>
        <div class="ag-preloader__overlay" aria-hidden="true"></div>
        @if ($preloaderShowSkip)
            <button type="button" class="ag-preloader__skip" id="agPreloaderSkip">Skip intro</button>
        @endif
    </div>
@endif

<div class="container-fluid fixed-top">
    <div class="container topbar">
        <div class="topbar-inner">
            @include('public.partials.topbar')
        </div>
    </div>
    <div class="container">
        <nav class="navbar navbar-light navbar-expand-lg py-3">
            <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
                <div class="ag-logo-video ag-logo-video--nav me-2" aria-label="{{ $shortName }} 3D logo">
                    <video class="ag-logo-video__el" src="{{ asset('site/videos/Create_a_cinematic_D_animatio.mp4') }}" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
                </div>
                <span class="mb-0 lh-sm">{{ $shortName }}</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-label="Toggle navigation">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                @include('public.partials.nav', ['navActive' => 'home'])
                <div class="navbar-cta-group align-items-center flex-shrink-0">
                    <a href="{{ route('public.sdtg') }}" class="btn btn-sdtg-nav" title="Visit {{ $sdtgLabel }} International Music Crusade">
                        <i class="fas fa-globe-africa" aria-hidden="true"></i>
                        <span>{{ $sdtgLabel }}</span>
                    </a>
                    <a href="{{ route('public.donate') }}" class="btn btn-primary py-2 px-4">Give</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="hero-slider" id="heroSlider" data-interval-ms="{{ $hero_interval_ms }}">
    @foreach ($hero_slides as $i => $slide)
        @php
            $image = (string) ($slide['image'] ?? app(\App\Services\PublicSite\PublicAssetResolver::class)->url('images/main1.jpg'));
            if (! str_starts_with($image, 'http') && ! str_starts_with($image, '/')) {
                $image = $toAsset($image);
            }
            $primaryLabel = trim((string) ($slide['primary_label'] ?? ''));
            $secondaryLabel = trim((string) ($slide['secondary_label'] ?? ''));
        @endphp
        <div class="hero-slide{{ $i === 0 ? ' active' : '' }}" data-slide="{{ $i }}">
            <div class="hero-slide-bg" style="background-image: url('{{ $image }}');"></div>
            <div class="hero-slide-overlay"></div>
            <div class="container hero-slide-content">
                <div class="col-lg-8 col-xl-7">
                    <div class="hero-header-inner">
                        <p class="fs-5 hero-tagline text-uppercase mb-3">{{ $slide['tagline'] ?? '' }}</p>
                        <h1 class="display-hero mb-4">{{ $slide['headline'] ?? '' }}</h1>
                        @if ($primaryLabel !== '')
                            <a href="{{ $toHref((string) ($slide['primary_url'] ?? '#')) }}" class="btn btn-primary py-3 px-5 me-2">{{ $primaryLabel }}</a>
                        @endif
                        @if ($secondaryLabel !== '')
                            <a href="{{ $toHref((string) ($slide['secondary_url'] ?? '#')) }}" class="btn btn-outline-primary py-3 px-5 bg-white">{{ $secondaryLabel }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@include('public.partials.about')
@include('public.partials.scripture-banner')

@php $homePage = is_array($homePage ?? null) ? $homePage : []; @endphp
<div class="container-fluid activities py-5">
    <div class="container py-5">
        <div class="mx-auto text-center mb-5 wow fadeIn" data-wow-delay="0.1s" style="max-width: 700px;">
            <p class="fs-5 text-uppercase text-primary">{{ $homePage['ministries_eyebrow'] ?? 'Ministries' }}</p>
            <h1 class="display-3">{{ $homePage['ministries_title'] ?? 'Serving God Through Every Season of Life' }}</h1>
        </div>
        @include('public.partials.activities')
    </div>
</div>

<section class="container-fluid ag-events-section py-5">
    <div class="container py-5 position-relative">
        <header class="ag-events-header text-center mx-auto mb-5 wow fadeIn" data-wow-delay="0.1s">
            <span class="ag-events-badge">{{ $homePage['events_eyebrow'] ?? 'Gather With Us' }}</span>
            <h2 class="display-4 mb-3">{{ $homePage['events_title'] ?? 'Upcoming Events' }}</h2>
            <p class="ag-events-intro mb-0">{{ $homePage['events_intro'] ?? 'Worship, study, and prayer — rhythm of life together at '.$shortName.'. Mark your calendar and bring someone along.' }}</p>
        </header>
        @include('public.partials.events')
        <div class="ag-events-footer text-center mt-5 pt-2 wow fadeIn" data-wow-delay="0.45s">
            <a href="{{ route('public.event') }}" class="btn btn-primary btn-lg px-5 py-3">View All Events</a>
        </div>
    </div>
</section>

@include('public.partials.sermons')
@include('public.partials.worship')
@include('public.partials.team')
@include('public.partials.testimonials')

<nav class="seo-internal-nav container" aria-label="Related pages">
    <h3>Explore More</h3>
    <div class="seo-internal-nav__grid">
        <a href="{{ route('public.sdtg.page', ['path' => 'registration']) }}">SDTG Registration</a>
        <a href="{{ route('public.sdtg.page', ['path' => 'donate']) }}">SDTG Donate</a>
        <a href="{{ route('public.sdtg.page', ['path' => 'gallery']) }}">SDTG Gallery</a>
        <a href="{{ route('public.sdtg.page', ['path' => 'livestream']) }}">SDTG Livestream</a>
        <a href="{{ route('public.contact') }}">Contact Us</a>
    </div>
</nav>

@include('public.partials.footer')
@endsection
