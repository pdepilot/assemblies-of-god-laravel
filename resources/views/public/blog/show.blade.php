@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'blog'])

@include('public.partials.page-hero', [
    'heroTitle' => $post['title'],
    'breadcrumbParent' => 'Blog',
    'breadcrumbParentUrl' => route('public.blog'),
    'breadcrumbCurrent' => \Illuminate\Support\Str::limit((string) $post['title'], 40),
    'heroUrl' => $post['image_url'] ?? null,
])

<div class="reading-progress" id="readingProgress" aria-hidden="true"><span></span></div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <img src="{{ $post['image_url'] }}" class="img-fluid rounded mb-4 w-100" style="max-height:420px;object-fit:cover" alt="{{ $post['image_alt'] ?? $post['title'] }}">
                <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                    <a href="{{ route('public.blog.category', $post['category'] ?? 'church-news') }}">{{ $post['category_label'] }}</a>
                    @if ($post['published_display'] !== '')
                        <span>{{ $post['published_display'] }}</span>
                    @endif
                    @if (! empty($post['author']))
                        <span>By {{ $post['author'] }}</span>
                    @endif
                    @if (! empty($post['reading_time_minutes']))
                        <span>{{ (int) $post['reading_time_minutes'] }} min read</span>
                    @endif
                </div>
                @if (! empty($post['excerpt']))
                    <p class="lead">{{ $post['excerpt'] }}</p>
                @endif

                @if (! empty($toc))
                    <nav class="blog-toc mb-4 p-3 bg-light rounded" aria-label="Table of contents">
                        <strong class="d-block mb-2">On this page</strong>
                        <ul class="mb-0 ps-3">
                            @foreach ($toc as $item)
                                <li class="{{ ($item['level'] ?? 2) === 3 ? 'ms-3' : '' }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <div class="blog-body">
                    {!! $post['body_html'] !!}
                </div>

                @if (! empty($post['tags']))
                    <div class="mt-4">
                        @foreach ($post['tags'] as $tag)
                            <a class="badge bg-primary text-decoration-none me-1" href="{{ route('public.blog.tag', $tag) }}">{{ $tag }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="blog-share mt-4 d-flex flex-wrap gap-2">
                    @php $shareUrl = urlencode(url()->current()); $shareTitle = urlencode((string) $post['title']); @endphp
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}">Facebook</a>
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}">X</a>
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}">WhatsApp</a>
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}">LinkedIn</a>
                </div>

                <div class="d-flex justify-content-between flex-wrap gap-3 mt-5">
                    @if (! empty($adjacent['previous']))
                        <a href="{{ route('public.blog.show', $adjacent['previous']['slug']) }}" class="btn btn-outline-primary">&larr; {{ \Illuminate\Support\Str::limit($adjacent['previous']['title'], 40) }}</a>
                    @else
                        <span></span>
                    @endif
                    @if (! empty($adjacent['next']))
                        <a href="{{ route('public.blog.show', $adjacent['next']['slug']) }}" class="btn btn-outline-primary">{{ \Illuminate\Support\Str::limit($adjacent['next']['title'], 40) }} &rarr;</a>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                @if (! empty($related))
                    <aside class="bg-light rounded p-4">
                        <h2 class="h5 mb-3">Related articles</h2>
                        @foreach ($related as $item)
                            <div class="mb-3">
                                <a href="{{ route('public.blog.show', $item['slug']) }}" class="fw-semibold text-dark">{{ $item['title'] }}</a>
                                <div class="small text-muted">{{ $item['category_label'] }}</div>
                            </div>
                        @endforeach
                    </aside>
                @endif
            </div>
        </div>
        <div class="mt-5">
            <a href="{{ route('public.blog') }}" class="btn btn-outline-primary">Back to blog</a>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection

@push('scripts')
<script src="{{ asset('site/js/reading-progress.js') }}" defer></script>
@endpush
