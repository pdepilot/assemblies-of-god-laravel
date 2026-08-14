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

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <img src="{{ $sermon['image_url'] }}" class="img-fluid rounded mb-4 w-100" style="max-height:420px;object-fit:cover" alt="{{ $sermon['title'] }}">
                <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                    @if (! empty($sermon['date_display']))
                        <span>{{ $sermon['date_display'] }}</span>
                    @endif
                    @if (! empty($sermon['minister_name']))
                        <span>{{ $sermon['minister_name'] }}</span>
                    @endif
                    @if (! empty($sermon['scripture_refs']))
                        <span>{{ $sermon['scripture_refs'] }}</span>
                    @endif
                </div>
                @if (! empty($sermon['description']))
                    <p class="lead">{{ $sermon['description'] }}</p>
                @endif

                @if (! empty($sermon['youtube_url']))
                    <div class="ratio ratio-16x9 mb-4">
                        <iframe src="{{ $sermon['youtube_url'] }}" title="{{ $sermon['title'] }}" allowfullscreen loading="lazy"></iframe>
                    </div>
                @elseif (! empty($sermon['audio_stream_url']) || ! empty($sermon['audio_file_path']))
                    <audio class="w-100 mb-4" controls src="{{ $sermon['audio_stream_url'] ?: asset('site/'.$sermon['audio_file_path']) }}"></audio>
                @endif

                <div class="blog-body">
                    {!! $sermon['content_html'] !!}
                </div>

                @if (! empty($sermon['pdf_file_path']))
                    <p class="mt-4"><a class="btn btn-outline-primary" href="{{ asset('site/'.$sermon['pdf_file_path']) }}" target="_blank" rel="noopener">Download notes (PDF)</a></p>
                @endif

                <div class="mt-5">
                    <a href="{{ route('public.sermons') }}" class="btn btn-outline-primary">Back to sermons</a>
                </div>
            </div>
            <div class="col-lg-4">
                @if (! empty($related))
                    <aside class="bg-light rounded p-4">
                        <h2 class="h5 mb-3">Related sermons</h2>
                        @foreach ($related as $item)
                            <div class="mb-3">
                                <a href="{{ route('public.sermons.show', $item['slug']) }}" class="fw-semibold text-dark">{{ $item['title'] }}</a>
                                <div class="small text-muted">{{ $item['date_display'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </aside>
                @endif
            </div>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection

@push('scripts')
<script src="{{ asset('site/js/reading-progress.js') }}" defer></script>
@endpush
