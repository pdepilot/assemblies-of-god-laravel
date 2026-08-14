@php
    $heading = (string) ($page['heading'] ?? 'Page');
    $eyebrow = trim((string) ($page['eyebrow'] ?? ''));
    $intro = trim((string) ($page['intro'] ?? ''));
    $body = (string) ($page['body_html'] ?? '');
    $ctaLabel = trim((string) ($page['cta_label'] ?? ''));
    $ctaUrl = trim((string) ($page['cta_url'] ?? ''));
    $heroUrl = $page['hero_image_url'] ?? null;
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if ($eyebrow !== '')
                    <p class="fs-5 text-uppercase text-primary">{{ $eyebrow }}</p>
                @endif
                @if ($intro !== '')
                    <p class="lead">{{ $intro }}</p>
                @endif
                @if (trim(strip_tags($body)) !== '')
                    <div class="cms-page-body">{!! $body !!}</div>
                @elseif (($pageKey ?? '') !== 'contact')
                    <p class="text-muted">Content for this page will appear here once it is added in Website → Pages.</p>
                @endif
                @if (($pageKey ?? '') === 'contact')
                    @include('public.partials.contact-form')
                @endif
                @if ($ctaLabel !== '')
                    <div class="mt-4">
                        <a href="{{ $toHref($ctaUrl !== '' ? $ctaUrl : 'contact') }}" class="btn btn-primary py-3 px-4">{{ $ctaLabel }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection
