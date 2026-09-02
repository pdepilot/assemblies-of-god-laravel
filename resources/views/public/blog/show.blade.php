@extends('layouts.public')

@push('head')
    <link href="{{ asset('site/css/blog.css') }}?v={{ @filemtime(public_path('site/css/blog.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $shareUrl = url()->current();
    $shareTitle = (string) ($post['title'] ?? '');
    $publishedLong = ! empty($post['published_at'])
        ? date('F j, Y', strtotime((string) $post['published_at']))
        : (string) ($post['published_display'] ?? '');
@endphp

@include('public.partials.site-chrome', ['navActive' => 'blog'])

@include('public.partials.page-hero', [
    'heroTitle' => $post['title'],
    'breadcrumbParent' => 'Blog',
    'breadcrumbParentUrl' => route('public.blog'),
    'breadcrumbCurrent' => 'Article',
    'heroUrl' => $post['image_url'] ?? null,
])

<div class="reading-progress" id="readingProgress" aria-hidden="true"><span></span></div>

<div class="container-xxl py-5">
    <div class="container">
        <div class="row g-4 g-xl-5">
            <aside class="col-lg-3 order-2 order-lg-1">
                <div class="blog-side-stack">
                    @if (! empty($toc))
                        <nav class="blog-toc surface-panel" aria-label="Table of contents">
                            <h2 class="blog-side-title">On this page</h2>
                            <ol>
                                @foreach ($toc as $item)
                                    <li class="toc-level-{{ $item['level'] ?? 2 }}">
                                        <a href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    @if (! empty($popular))
                        <div class="surface-panel sticky-panel">
                            <h2 class="blog-side-title">Popular reads</h2>
                            <ul class="blog-mini-list">
                                @foreach ($popular as $item)
                                    <li>
                                        <a href="{{ route('public.blog.show', $item['slug']) }}">{{ $item['title'] }}</a>
                                        <span>
                                            {{ $item['reading_time_display'] ?? '1 min read' }}
                                            @if (($item['view_count_display'] ?? 0) > 0)
                                                · {{ number_format((int) $item['view_count_display']) }} views
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </aside>

            <div class="col-lg-7 order-1 order-lg-2">
                <article class="blog-article wow fadeInUp" data-wow-delay="0.1s" id="blogArticle">
                    @if (! empty($post['image_url']))
                        <img
                            class="blog-article-cover mb-4"
                            src="{{ $post['image_url'] }}"
                            alt="{{ $post['image_alt'] ?? $post['title'] }}"
                            loading="eager"
                        >
                    @endif

                    <div class="blog-article-meta mb-4">
                        <p class="blog-card-meta mb-2">
                            @if ($publishedLong !== '')
                                {{ $publishedLong }}
                            @endif
                            @if (! empty($post['author_display']))
                                · {{ $post['author_display'] }}
                            @endif
                            · <span id="readingTime">{{ $post['reading_time_display'] ?? '1 min read' }}</span>
                            @if (($post['view_count_display'] ?? 0) > 0)
                                · {{ number_format((int) $post['view_count_display']) }} views
                            @endif
                        </p>
                        @if (! empty($post['tags']))
                            <div class="blog-tags">
                                @foreach ($post['tags'] as $tag)
                                    <a href="{{ route('public.blog.tag', $tag) }}">{{ str_replace('-', ' ', $tag) }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="blog-article-body" id="articleBody">
                        {!! $post['body_html'] !!}
                    </div>

                    <nav class="blog-pager mt-5" aria-label="Article navigation">
                        <div>
                            @if (! empty($adjacent['previous']))
                                <a href="{{ route('public.blog.show', $adjacent['previous']['slug']) }}">
                                    <span>Previous</span>
                                    <strong>{{ $adjacent['previous']['title'] }}</strong>
                                </a>
                            @endif
                        </div>
                        <div class="text-lg-end">
                            @if (! empty($adjacent['next']))
                                <a href="{{ route('public.blog.show', $adjacent['next']['slug']) }}">
                                    <span>Next</span>
                                    <strong>{{ $adjacent['next']['title'] }}</strong>
                                </a>
                            @endif
                        </div>
                    </nav>

                    <div class="mt-4">
                        <a href="{{ route('public.blog') }}" class="btn btn-primary py-2 px-4">&larr; Back to blog</a>
                    </div>
                </article>
            </div>

            <aside class="col-lg-2 order-3">
                <div class="blog-share sticky-share" aria-label="Share this article">
                    <span class="blog-side-title">Share</span>
                    <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a class="share-btn" href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}" target="_blank" rel="noopener" aria-label="Share on X"><i class="fab fa-twitter"></i></a>
                    <a class="share-btn" href="https://api.whatsapp.com/send?text={{ urlencode($shareTitle.' '.$shareUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a class="share-btn" href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($shareUrl) }}&title={{ urlencode($shareTitle) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <button class="share-btn" type="button" id="copyArticleLink" data-share-url="{{ $shareUrl }}" aria-label="Copy link"><i class="fas fa-link"></i></button>
                </div>
            </aside>
        </div>

        @if (! empty($related))
            <div class="mt-5 pt-4">
                <h2 class="mb-4">Related content</h2>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-lg-4 col-md-6">
                            <article class="blog-card h-100">
                                <a href="{{ route('public.blog.show', $item['slug']) }}" class="blog-card-media">
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['image_alt'] ?? $item['title'] }}">
                                </a>
                                <div class="blog-card-body">
                                    <p class="blog-card-meta">{{ $item['reading_time_display'] ?? '1 min read' }}</p>
                                    <h3 class="blog-card-title">
                                        <a href="{{ route('public.blog.show', $item['slug']) }}">{{ $item['title'] }}</a>
                                    </h3>
                                    <p class="blog-card-excerpt">{{ \Illuminate\Support\Str::limit((string) ($item['excerpt'] ?? ''), 140) }}</p>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@include('public.partials.footer')
@endsection

@push('scripts')
<script src="{{ asset('site/js/reading-progress.js') }}" defer></script>
<script src="{{ asset('site/js/blog-ux.js') }}?v={{ filemtime(public_path('site/js/blog-ux.js')) }}" defer></script>
<script type="application/json" id="blogArticleAnalytics">{!! json_encode(['item_id' => (string) ($post['slug'] ?? ''), 'page_title' => (string) ($post['title'] ?? '')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!(window.AG_ANALYTICS && typeof window.AG_ANALYTICS.event === 'function')) {
        return;
    }

    var payload = { content_type: 'blog_post', item_id: '', page_title: '' };
    var flagsEl = document.getElementById('blogArticleAnalytics');
    if (flagsEl) {
        try {
            var flags = JSON.parse(flagsEl.textContent || '{}');
            payload.item_id = flags.item_id || '';
            payload.page_title = flags.page_title || '';
        } catch (e) { /* ignore */ }
    }

    window.AG_ANALYTICS.event('article_view', payload);
});
</script>
@endpush
