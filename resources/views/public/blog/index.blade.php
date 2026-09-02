@extends('layouts.public')

@push('head')
    <link href="{{ asset('site/css/blog.css') }}?v={{ filemtime(public_path('site/css/blog.css')) }}" rel="stylesheet">
@endpush

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
    $intro = trim((string) ($page['intro'] ?? '')) !== '' ? (string) $page['intro'] : 'News, devotionals, and updates from AGC Ikenegbu';
    $heroUrl = $page['hero_image_url'] ?? null;
    $postsPage = max(1, (int) ($posts['page'] ?? 1));
    $postsPerPage = max(1, (int) ($posts['per_page'] ?? 9));
    $postsTotal = (int) ($posts['total'] ?? 0);
    $postsPages = max(1, (int) ($posts['pages'] ?? 1));
    $postsFrom = $postsTotal > 0 ? (($postsPage - 1) * $postsPerPage) + 1 : 0;
    $postsTo = min($postsPage * $postsPerPage, $postsTotal);
    $searchAction = route('public.blog');
    if (($activeCategory ?? '') !== '') {
        $searchAction = route('public.blog.category', $activeCategory);
    } elseif (($activeTag ?? '') !== '') {
        $searchAction = route('public.blog.tag', $activeTag);
    }
@endphp

@include('public.partials.site-chrome', ['navActive' => 'blog'])

@include('public.partials.page-hero', [
    'heroTitle' => $heading,
    'breadcrumbCurrent' => 'Blog',
    'heroUrl' => $heroUrl,
])

<div class="container-xxl py-5">
    <div class="container">
        <div class="text-center mx-auto wow fadeInUp mb-4" data-wow-delay="0.1s" style="max-width: 620px;">
            <p class="section-title bg-white text-center text-primary px-3">{{ $eyebrow }}</p>
            <h2 class="mb-3">{{ $intro }}</h2>
        </div>

        <div class="blog-search-wrap wow fadeInUp mb-5" data-wow-delay="0.15s">
            <form action="{{ $searchAction }}" method="get" class="blog-search-form" role="search" autocomplete="off">
                <label class="visually-hidden" for="blogSearchInput">Search articles</label>
                <input
                    id="blogSearchInput"
                    type="search"
                    name="q"
                    value="{{ $searchQuery ?? '' }}"
                    placeholder="Search articles, devotionals, and church news."
                    aria-autocomplete="list"
                    aria-controls="blogSuggestList"
                    aria-expanded="false"
                >
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <ul id="blogSuggestList" class="blog-suggest-list" role="listbox" hidden></ul>
            @if (trim((string) ($searchQuery ?? '')) !== '')
                <p class="blog-search-hint mt-3 mb-0 text-center">
                    Showing results for <strong>{{ $searchQuery }}</strong>.
                    <a href="{{ $searchAction }}">Clear search</a>
                </p>
            @elseif (($activeCategory ?? '') !== '')
                <p class="blog-search-hint mt-3 mb-0 text-center">
                    Filtered by category: <strong>{{ $heading }}</strong>.
                    <a href="{{ route('public.blog') }}">View all posts</a>
                </p>
            @elseif (trim((string) ($activeTag ?? '')) !== '')
                <p class="blog-search-hint mt-3 mb-0 text-center">
                    Filtered by tag: <strong>{{ $activeTag }}</strong>.
                    <a href="{{ route('public.blog') }}">View all posts</a>
                </p>
            @endif
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                @if (($posts['items'] ?? []) === [])
                    <p class="blog-empty-state text-center">No published posts yet. Check back soon.</p>
                @else
                    <div class="row g-4">
                        @foreach ($posts['items'] as $post)
                            <div class="col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                                <article class="blog-card h-100">
                                    <a href="{{ route('public.blog.show', $post['slug']) }}" class="blog-card-media">
                                        <img src="{{ $post['image_url'] }}" alt="{{ $post['image_alt'] ?? $post['title'] }}">
                                    </a>
                                    <div class="blog-card-body">
                                        <p class="blog-card-meta">
                                            {{ $post['published_display'] }}
                                            · {{ $post['reading_time_display'] }}
                                            · {{ $post['author_display'] }}
                                        </p>
                                        <h3 class="blog-card-title">
                                            <a href="{{ route('public.blog.show', $post['slug']) }}">{{ $post['title'] }}</a>
                                        </h3>
                                        <p class="blog-card-excerpt">{{ \Illuminate\Support\Str::limit((string) ($post['excerpt'] ?? ''), 180) }}</p>
                                        <a class="btn btn-secondary py-2 px-4" href="{{ route('public.blog.show', $post['slug']) }}">Read more</a>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    @if ($postsPages > 1)
                        <nav class="blog-index-pager mt-5" aria-label="Blog pages">
                            <nav class="d-flex justify-items-center justify-content-between">
                                <div class="d-flex justify-content-between flex-fill d-sm-none">
                                    <ul class="pagination mb-0">
                                        <li class="page-item {{ $postsPage <= 1 ? 'disabled' : '' }}" @if ($postsPage <= 1) aria-disabled="true" @endif>
                                            @if ($postsPage <= 1)
                                                <span class="page-link">&laquo; Previous</span>
                                            @else
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $postsPage - 1]) }}" rel="prev">&laquo; Previous</a>
                                            @endif
                                        </li>
                                        <li class="page-item {{ $postsPage >= $postsPages ? 'disabled' : '' }}" @if ($postsPage >= $postsPages) aria-disabled="true" @endif>
                                            @if ($postsPage >= $postsPages)
                                                <span class="page-link">Next &raquo;</span>
                                            @else
                                                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $postsPage + 1]) }}" rel="next">Next &raquo;</a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>

                                <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
                                    <div class="small text-muted">
                                        Showing
                                        <span class="fw-semibold">{{ $postsFrom }}</span>
                                        to
                                        <span class="fw-semibold">{{ $postsTo }}</span>
                                        of
                                        <span class="fw-semibold">{{ $postsTotal }}</span>
                                        results
                                    </div>

                                    <div>
                                        <ul class="pagination mb-0">
                                            <li class="page-item {{ $postsPage <= 1 ? 'disabled' : '' }}" @if ($postsPage <= 1) aria-disabled="true" aria-label="Previous" @endif>
                                                @if ($postsPage <= 1)
                                                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                                @else
                                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $postsPage - 1]) }}" rel="prev" aria-label="Previous">&lsaquo;</a>
                                                @endif
                                            </li>
                                            @for ($p = 1; $p <= $postsPages; $p++)
                                                <li class="page-item {{ $p === $postsPage ? 'active' : '' }}" @if ($p === $postsPage) aria-current="page" @endif>
                                                    @if ($p === $postsPage)
                                                        <span class="page-link">{{ $p }}</span>
                                                    @else
                                                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                                                    @endif
                                                </li>
                                            @endfor
                                            <li class="page-item {{ $postsPage >= $postsPages ? 'disabled' : '' }}" @if ($postsPage >= $postsPages) aria-disabled="true" aria-label="Next" @endif>
                                                @if ($postsPage >= $postsPages)
                                                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                                                @else
                                                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $postsPage + 1]) }}" rel="next" aria-label="Next">&rsaquo;</a>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </nav>
                        </nav>
                    @endif
                @endif
            </div>

            <aside class="col-lg-4">
                @if (! empty($popular))
                    <div class="surface-panel sticky-panel">
                        <h2 class="blog-side-title">Popular content</h2>
                        <ul class="blog-mini-list">
                            @foreach ($popular as $item)
                                <li>
                                    <a href="{{ route('public.blog.show', $item['slug']) }}">{{ $item['title'] }}</a>
                                    <span>
                                        {{ $item['reading_time_display'] }}
                                        @if (($item['view_count_display'] ?? 0) > 0)
                                            · {{ number_format((int) $item['view_count_display']) }} views
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection

@push('scripts')
<script src="{{ asset('site/js/blog-ux.js') }}?v={{ filemtime(public_path('site/js/blog-ux.js')) }}" defer></script>
@if (trim((string) ($searchQuery ?? '')) !== '')
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
@endif
@endpush
