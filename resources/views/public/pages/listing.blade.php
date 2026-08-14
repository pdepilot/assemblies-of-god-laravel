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

@include('public.partials.page-hero', [
    'heroTitle' => $heading,
    'breadcrumbCurrent' => $heading,
    'heroUrl' => $heroUrl,
])

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
