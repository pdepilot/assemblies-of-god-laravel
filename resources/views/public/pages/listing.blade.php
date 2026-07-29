@php
    $heading = (string) ($page['heading'] ?? 'Page');
    $eyebrow = trim((string) ($page['eyebrow'] ?? ''));
    $intro = trim((string) ($page['intro'] ?? ''));
    $ctaLabel = trim((string) ($page['cta_label'] ?? ''));
    $ctaUrl = trim((string) ($page['cta_url'] ?? ''));
    $heroUrl = $page['hero_image_url'] ?? null;
    $legacy_base = $legacy_base ?? url('/');
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
    $toHref = function (string $path): string {
        return app(\App\Services\PublicSite\PublicHomepageReadService::class)->legacyUrl(ltrim($path, '/'));
    };
@endphp
@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => $navActive ?? ''])

<div class="container-fluid page-header py-5" @if ($heroUrl) style="background-image:linear-gradient(rgba(26,43,92,.75),rgba(26,43,92,.75)),url('{{ $heroUrl }}');background-size:cover;background-position:center;" @endif>
    <div class="container text-center py-5">
        <h1 class="display-2 text-white mb-3 animated slideInDown">{{ $heading }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">{{ $heading }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        @if ($pageKey !== 'sermons' && ($eyebrow !== '' || $intro !== ''))
            <div class="text-center mx-auto mb-5" style="max-width:720px">
                @if ($eyebrow !== '')
                    <p class="fs-5 text-uppercase text-primary">{{ $eyebrow }}</p>
                @endif
                @if ($intro !== '')
                    <p class="mb-0">{{ $intro }}</p>
                @endif
            </div>
        @endif

        @if ($pageKey === 'activity')
            @include('public.partials.activities')
        @elseif ($pageKey === 'event')
            @include('public.partials.events')
        @elseif ($pageKey === 'sermons')
            @include('public.partials.sermons')
        @endif

        @if ($ctaLabel !== '')
            <div class="text-center mt-5">
                <a href="{{ $toHref($ctaUrl !== '' ? $ctaUrl : 'contact') }}" class="btn btn-primary py-3 px-4">{{ $ctaLabel }}</a>
            </div>
        @endif
    </div>
</div>

@include('public.partials.footer')
@endsection
