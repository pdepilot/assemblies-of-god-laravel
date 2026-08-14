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

@include('public.partials.page-hero', [
    'heroTitle' => $headerTitle,
    'breadcrumbHome' => $hero['breadcrumb_home_label'] ?? 'Home',
    'breadcrumbParent' => $hero['breadcrumb_parent_label'] ?? 'Pages',
    'breadcrumbCurrent' => $hero['breadcrumb_current'] ?? 'About',
    'heroUrl' => $heroUrl,
])

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

@include('public.partials.team')

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
