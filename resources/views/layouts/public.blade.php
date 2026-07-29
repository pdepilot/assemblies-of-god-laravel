<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="AG Ikenebgu Assemblies of God">
    <meta name="theme-color" content="#1A2B5C">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle = $seo['title'] ?? null;
        $seoDescription = $seo['meta_description'] ?? null;
        $seoCanonical = $seo['canonical'] ?? url()->current();
        $seoOgImage = trim((string) ($seo['og_image'] ?? ''));
        if ($seoOgImage !== '' && ! str_starts_with($seoOgImage, 'http')) {
            $seoOgImage = app(\App\Services\PublicSite\PublicAssetResolver::class)->url($seoOgImage);
        }
        if ($seoOgImage === '') {
            $seoOgImage = asset('site/images/ag-logo.jpeg');
        }
    @endphp
    <title>{{ $seoTitle ?: 'AG Ikenebgu | Assemblies of God Church Owerri — Worship & Community' }}</title>
    <meta name="description" content="{{ $seoDescription ?: 'AG Ikenebgu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, family ministries, and community outreach. Join us Sundays 8:00 AM & 10:30 AM.' }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AG Ikenebgu">
    <meta property="og:title" content="{{ $seoTitle ?: 'AG Ikenebgu | Assemblies of God Church Owerri' }}">
    <meta property="og:description" content="{{ $seoDescription ?: 'Spirit-filled worship, Bible teaching, and community outreach in Owerri, Nigeria.' }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoOgImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle ?: 'AG Ikenebgu | Assemblies of God Church Owerri' }}">
    <meta name="twitter:description" content="{{ $seoDescription ?: 'Spirit-filled worship, Bible teaching, and community outreach in Owerri, Nigeria.' }}">
    <meta name="twitter:image" content="{{ $seoOgImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('site/lib/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('site/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/brand.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/preloader.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/ag-cookie-banner.css') }}" rel="stylesheet">
    <link href="{{ asset('site/css/seo-components.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('site/images/ag-logo.jpeg') }}" type="image/jpeg">
    <script src="{{ asset('site/js/seo-performance.js') }}" defer></script>
    @stack('head')
</head>
<body class="ag-site-body" data-testimony-source="{{ $testimonySourcePage ?? 'index' }}" data-api-base="{{ $legacy_api_base }}" data-traffic-endpoint="{{ $traffic_beacon_url }}">
    @yield('content')

    <a href="#" class="btn btn-primary border-3 border-light back-to-top"><i class="fa fa-arrow-up"></i></a>

    @include('public.partials.cookie-banner')

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('site/lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('site/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('site/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('site/lib/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('site/js/preloader.js') }}"></script>
    <script src="{{ asset('site/js/testimony-display.js') }}"></script>
    <script src="{{ asset('site/js/testimony-form.js') }}"></script>
    <script src="{{ asset('site/js/main.js') }}"></script>
    <script src="{{ asset('site/js/ag-cookie-banner.js') }}"></script>
    <script>
        window.AG_SITE_TRAFFIC = window.AG_SITE_TRAFFIC || {
            endpoint: document.body.getAttribute('data-traffic-endpoint')
        };
    </script>
    <script src="{{ asset('site/js/site-traffic.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
