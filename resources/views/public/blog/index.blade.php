@extends('layouts.public')

@section('content')
@php
    $page = is_array($page ?? null) ? $page : [];
    $defaultHeading = (string) (app(\App\Services\Website\WebsitePagesReadService::class)->defaultPageContent('blog')['heading'] ?? 'Blog');
    $rawHeading = trim((string) ($page['heading'] ?? ''));
    $heading = trim((string) ($listingHeading ?? ''));
    if ($heading === '' || strcasecmp($heading, 'Blog') === 0) {
        $heading = ($rawHeading !== '' && strcasecmp($rawHeading, $defaultHeading) !== 0) ? $rawHeading : 'Church Blog';
    }
    $eyebrow = trim((string) ($page['eyebrow'] ?? '')) !== '' ? (string) $page['eyebrow'] : 'From the Church';
    $intro = trim((string) ($page['intro'] ?? '')) !== '' ? (string) $page['intro'] : 'News, devotionals, and updates';
    $heroUrl = $page['hero_image_url'] ?? null;
@endphp

@include('public.partials.site-chrome', ['navActive' => 'blog'])

@include('public.partials.page-hero', [
    'heroTitle' => $heading,
    'breadcrumbCurrent' => 'Blog',
    'heroUrl' => $heroUrl,
])

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-4" style="max-width:720px">
            <p class="fs-5 text-uppercase text-primary">{{ $eyebrow }}</p>
            <h2 class="display-5">{{ $intro }}</h2>
        </div>

        <form method="GET" action="{{ route('public.blog') }}" class="row g-2 justify-content-center mb-5">
            <div class="col-md-6">
                <input type="search" name="q" value="{{ $searchQuery ?? '' }}" class="form-control" placeholder="Search articles…">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <div class="row g-4 mb-5">
            <div class="col-lg-8">
                @if (($posts['items'] ?? []) === [])
                    <p class="text-center text-muted">No published posts yet. Check back soon.</p>
                @else
                    <div class="row g-4">
                        @foreach ($posts['items'] as $post)
                            <div class="col-md-6">
                                <article class="bg-light h-100 rounded overflow-hidden">
                                    <img src="{{ $post['image_url'] }}" class="img-fluid w-100" style="height:220px;object-fit:cover" alt="{{ $post['image_alt'] ?? $post['title'] }}">
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
                                <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}" class="btn {{ $p === ($posts['page'] ?? 1) ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">{{ $p }}</a>
                            @endfor
                        </div>
                    @endif
                @endif
            </div>
            <div class="col-lg-4">
                <aside class="bg-light rounded p-4 mb-4">
                    <h2 class="h5">Categories</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach (($categories ?? []) as $slug => $label)
                            <li class="mb-1"><a href="{{ route('public.blog.category', $slug) }}" class="{{ ($activeCategory ?? '') === $slug ? 'fw-bold' : '' }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </aside>
                @if (! empty($popular))
                    <aside class="bg-light rounded p-4 mb-4">
                        <h2 class="h5">Popular</h2>
                        @foreach ($popular as $item)
                            <div class="mb-2"><a href="{{ route('public.blog.show', $item['slug']) }}">{{ $item['title'] }}</a></div>
                        @endforeach
                    </aside>
                @endif
                @if (! empty($recent))
                    <aside class="bg-light rounded p-4">
                        <h2 class="h5">Recent</h2>
                        @foreach ($recent as $item)
                            <div class="mb-2"><a href="{{ route('public.blog.show', $item['slug']) }}">{{ $item['title'] }}</a></div>
                        @endforeach
                    </aside>
                @endif
            </div>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection

@if (trim((string) ($searchQuery ?? '')) !== '')
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.AG_ANALYTICS && typeof window.AG_ANALYTICS.event === 'function') {
            window.AG_ANALYTICS.event('search', {
                search_term: @json($searchQuery),
                content_type: 'blog'
            });
        }
    });
    </script>
    @endpush
@endif
