@extends('layouts.public')

@section('content')
@php
    $hero = is_array($about['hero'] ?? null) ? $about['hero'] : [];
    $cta = is_array($about['cta_banner'] ?? null) ? $about['cta_banner'] : [];
    $page = is_array($page ?? null) ? $page : [];
    $pageHeading = trim((string) ($page['heading'] ?? ''));
    $pageIntro = trim((string) ($page['intro'] ?? ''));
    $pageEyebrow = trim((string) ($page['eyebrow'] ?? ''));
    $heroUrl = $page['hero_image_url'] ?? null;
    $defaultPageHeading = (string) (app(\App\Services\Website\WebsitePagesReadService::class)->defaultPageContent('about')['heading'] ?? 'About');
    $headerTitle = ($pageHeading !== '' && strcasecmp($pageHeading, $defaultPageHeading) !== 0)
        ? $pageHeading
        : ($hero['title'] ?? ($pageHeading !== '' ? $pageHeading : 'About Us'));
    $toHref = function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl($path);
    };
@endphp


@include('public.partials.site-chrome', ['navActive' => 'about'])

<div class="container-fluid page-header py-5" @if ($heroUrl) style="background-image:linear-gradient(rgba(26,43,92,.75),rgba(26,43,92,.75)),url('{{ $heroUrl }}');background-size:cover;background-position:center;" @endif>
    <div class="container text-center py-5">
        <h1 class="display-2 text-white mb-3 animated slideInDown">{{ $headerTitle }}</h1>
        <nav aria-label="breadcrumb" class="animated slideInDown">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ $hero['breadcrumb_home_label'] ?? 'Home' }}</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">{{ $hero['breadcrumb_current'] ?? 'About' }}</li>
            </ol>
        </nav>
    </div>
</div>

@if ($pageEyebrow !== '' || $pageIntro !== '')
    <div class="container-fluid pt-5">
        <div class="container">
            <div class="text-center mx-auto" style="max-width:720px">
                @if ($pageEyebrow !== '')
                    <p class="fs-5 text-uppercase text-primary">{{ $pageEyebrow }}</p>
                @endif
                @if ($pageIntro !== '')
                    <p class="lead mb-0">{{ $pageIntro }}</p>
                @endif
            </div>
        </div>
    </div>
@endif

@include('public.partials.about')

@if (! empty($cta['title']) || ! empty($cta['cta_label']) || ! empty($page['cta_label']))
    <div class="container-fluid py-5 bg-primary">
        <div class="container py-4 text-center text-white">
            @if (! empty($cta['title']))
                <h2 class="display-6 text-white mb-4">{{ $cta['title'] }}</h2>
            @endif
            @php
                $ctaLabel = trim((string) (($page['cta_label'] ?? '') !== '' ? $page['cta_label'] : ($cta['cta_label'] ?? '')));
                $ctaUrl = trim((string) (($page['cta_url'] ?? '') !== '' ? $page['cta_url'] : ($cta['cta_url'] ?? 'contact')));
            @endphp
            @if ($ctaLabel !== '')
                <a href="{{ $toHref($ctaUrl !== '' ? $ctaUrl : 'contact') }}" class="btn btn-light py-3 px-5">{{ $ctaLabel }}</a>
            @endif
        </div>
    </div>
@endif

@include('public.partials.footer')
@endsection
