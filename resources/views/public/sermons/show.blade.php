@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'sermons'])

@include('public.partials.page-hero', [
    'heroTitle' => $sermon['title'],
    'breadcrumbParent' => 'Sermons',
    'breadcrumbParentUrl' => route('public.sermons'),
    'breadcrumbCurrent' => \Illuminate\Support\Str::limit((string) $sermon['title'], 40),
    'heroUrl' => $sermon['image_url'] ?? null,
])

<div class="reading-progress" id="readingProgress" aria-hidden="true"><span></span></div>

<section class="container-fluid sermon-detail py-5">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-lg-8">
                <article class="sermon-detail-main">
                    @if (! empty($sermon['video_file_url']))
                        <div class="sermon-detail-player">
                            <video class="w-100" controls playsinline preload="metadata" @if (! empty($sermon['image_url'])) poster="{{ $sermon['image_url'] }}" @endif src="{{ $sermon['video_file_url'] }}"></video>
                        </div>
                    @elseif (! empty($sermon['youtube_embed_url']))
                        <div class="sermon-detail-player ratio ratio-16x9">
                            <iframe src="{{ $sermon['youtube_embed_url'] }}" title="{{ $sermon['title'] }}" allowfullscreen loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        </div>
                    @elseif (! empty($sermon['vimeo_embed_url']))
                        <div class="sermon-detail-player ratio ratio-16x9">
                            <iframe src="{{ $sermon['vimeo_embed_url'] }}" title="{{ $sermon['title'] }}" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @else
                        <div class="sermon-detail-cover">
                            <img src="{{ $sermon['image_url'] }}" alt="{{ $sermon['title'] }}">
                        </div>
                    @endif

                    <div class="sermon-library-chips mb-3">
                        @if (! empty($sermon['has_video']))<span class="sermon-library-chip sermon-library-chip--accent">Video</span>@endif
                        @if (! empty($sermon['has_audio']))<span class="sermon-library-chip">Audio</span>@endif
                        @if (! empty($sermon['has_pdf']))<span class="sermon-library-chip">Notes</span>@endif
                    </div>

                    <h1 class="sermon-detail-title">{{ $sermon['title'] }}</h1>
                    <p class="sermon-library-meta sermon-library-meta--lg">
                        @if (! empty($sermon['date_display']))<span><i class="fa fa-calendar-alt"></i>{{ $sermon['date_display'] }}</span>@endif
                        @if (! empty($sermon['minister_name']))<span><i class="fa fa-user"></i>{{ $sermon['minister_name'] }}</span>@endif
                        @if (! empty($sermon['scripture_refs']))<span><i class="fa fa-book-open"></i>{{ $sermon['scripture_refs'] }}</span>@endif
                    </p>

                    @if (! empty($sermon['description']))
                        <p class="lead sermon-detail-lead">{{ $sermon['description'] }}</p>
                    @endif

                    @if (! empty($sermon['audio_url']))
                        <div class="sermon-detail-audio">
                            <p>Listen</p>
                            <audio class="w-100" controls src="{{ $sermon['audio_url'] }}"></audio>
                        </div>
                    @endif

                    @if (! empty($sermon['content_html']))
                        <div class="blog-body sermon-detail-body">
                            {!! $sermon['content_html'] !!}
                        </div>
                    @endif

                    @if (! empty($sermon['pdf_url']))
                        <p class="mt-4 mb-0">
                            <a class="btn btn-primary" href="{{ $sermon['pdf_url'] }}" target="_blank" rel="noopener">Download notes (PDF)</a>
                        </p>
                    @endif
                </article>
            </div>
            <div class="col-lg-4">
                <aside class="sermon-detail-aside">
                    <h2>Related sermons</h2>
                    @if (! empty($related))
                        <ul class="sermon-detail-related">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ $item['url'] }}">
                                        <img src="{{ $item['image_url'] }}" alt="">
                                        <span>
                                            <strong>{{ $item['title'] }}</strong>
                                            <small>{{ $item['date_display'] ?? '' }}</small>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-0">More messages will appear here as they are published.</p>
                    @endif
                    <a class="btn btn-outline-primary w-100 mt-4" href="{{ route('public.sermons') }}">Back to sermons</a>
                </aside>
            </div>
        </div>
    </div>
</section>

@include('public.partials.footer')
@endsection

@push('scripts')
<script src="{{ asset('site/js/reading-progress.js') }}" defer></script>
@endpush
