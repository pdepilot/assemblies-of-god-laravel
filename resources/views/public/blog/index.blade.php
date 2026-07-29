@extends('layouts.public')

@section('content')
@php
    $page = is_array($page ?? null) ? $page : [];
    $defaultHeading = (string) (app(\App\Services\Website\WebsitePagesReadService::class)->defaultPageContent('blog')['heading'] ?? 'Blog');
    $rawHeading = trim((string) ($page['heading'] ?? ''));
    $heading = ($rawHeading !== '' && strcasecmp($rawHeading, $defaultHeading) !== 0) ? $rawHeading : 'Church Blog';
    $eyebrow = trim((string) ($page['eyebrow'] ?? '')) !== '' ? (string) $page['eyebrow'] : 'From the Church';
    $intro = trim((string) ($page['intro'] ?? '')) !== '' ? (string) $page['intro'] : 'News, devotionals, and updates';
    $heroUrl = $page['hero_image_url'] ?? null;
@endphp


@include('public.partials.site-chrome', ['navActive' => 'blog'])

<div class="container-fluid page-header py-5" @if ($heroUrl) style="background-image:linear-gradient(rgba(26,43,92,.75),rgba(26,43,92,.75)),url('{{ $heroUrl }}');background-size:cover;background-position:center;" @endif>
    <div class="container text-center py-5">
        <h1 class="display-2 text-white mb-3 animated slideInDown">{{ $heading }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">Blog</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5" style="max-width:720px">
            <p class="fs-5 text-uppercase text-primary">{{ $eyebrow }}</p>
            <h2 class="display-5">{{ $intro }}</h2>
        </div>

        @if (($posts['items'] ?? []) === [])
            <p class="text-center text-muted">No published posts yet. Check back soon.</p>
        @else
            <div class="row g-4">
                @foreach ($posts['items'] as $post)
                    <div class="col-md-6 col-lg-4">
                        <article class="bg-light h-100 rounded overflow-hidden">
                            <img src="{{ $post['image_url'] }}" class="img-fluid w-100" style="height:220px;object-fit:cover" alt="{{ $post['title'] }}">
                            <div class="p-4">
                                <div class="d-flex justify-content-between text-muted small mb-2">
                                    <span>{{ $post['category_label'] }}</span>
                                    <span>{{ $post['published_display'] }}</span>
                                </div>
                                <h3 class="h5"><a class="text-dark" href="{{ route('public.blog.show', $post['slug']) }}">{{ $post['title'] }}</a></h3>
                                <p class="mb-3">{{ \Illuminate\Support\Str::limit((string) ($post['excerpt'] ?? ''), 140) }}</p>
                                <a href="{{ route('public.blog.show', $post['slug']) }}" class="btn btn-primary btn-sm">Read more</a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            @if (($posts['pages'] ?? 1) > 1)
                <div class="d-flex justify-content-center gap-2 mt-5">
                    @for ($p = 1; $p <= $posts['pages']; $p++)
                        <a href="{{ route('public.blog', ['page' => $p]) }}" class="btn {{ $p === ($posts['page'] ?? 1) ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">{{ $p }}</a>
                    @endfor
                </div>
            @endif
        @endif
    </div>
</div>

@include('public.partials.footer')
@endsection
