@php
    $heading = trim((string) ($page['heading'] ?? '')) ?: 'Sermons';
    $eyebrow = trim((string) ($page['eyebrow'] ?? '')) ?: 'The Word';
    $intro = trim((string) ($page['intro'] ?? '')) ?: 'Watch, listen, and grow through messages preached at AGC Ikenegbu.';
    $heroUrl = $page['hero_image_url'] ?? null;
    $church = is_array($church ?? null) ? $church : [];
    $sunday = trim((string) ($church['sunday_worship'] ?? ''));
    $library = is_array($library ?? null) ? $library : ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
    $items = $library['items'] ?? [];
    $searchQuery = trim((string) ($searchQuery ?? ''));
    $activeType = trim((string) ($activeType ?? ''));
    $pageNum = max(1, (int) ($library['page'] ?? 1));
    $pages = max(1, (int) ($library['pages'] ?? 1));
    $total = (int) ($library['total'] ?? 0);
    $featured = null;
    $grid = $items;
    if ($pageNum === 1 && $searchQuery === '' && $activeType === '' && $items !== []) {
        $featured = $items[0];
        $grid = array_slice($items, 1);
    }
    $filterUrl = static function (?string $type, ?string $q = null) use ($searchQuery): string {
        $query = [];
        $q = $q ?? $searchQuery;
        if (trim((string) $q) !== '') {
            $query['q'] = $q;
        }
        if ($type) {
            $query['type'] = $type;
        }

        return route('public.sermons', $query);
    };
@endphp
@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'sermons'])

@include('public.partials.page-hero', [
    'heroTitle' => $heading,
    'breadcrumbCurrent' => $heading,
    'heroUrl' => $heroUrl,
])

<section class="container-fluid sermon-library py-5">
    <div class="container py-4">
        <div class="sermon-library-header text-center mx-auto mb-5">
            <span class="sermon-library-badge">{{ $eyebrow }}</span>
            <h2 class="display-6 mb-3">Latest Messages</h2>
            <p class="lead mb-0">{{ $intro }}</p>
        </div>

        <div class="sermon-library-toolbar">
            <form class="sermon-library-search" method="get" action="{{ route('public.sermons') }}" role="search">
                @if ($activeType !== '')
                    <input type="hidden" name="type" value="{{ $activeType }}">
                @endif
                <label class="visually-hidden" for="sermonSearch">Search sermons</label>
                <span class="sermon-library-search__icon" aria-hidden="true"><i class="fa fa-search"></i></span>
                <input id="sermonSearch" type="search" name="q" value="{{ $searchQuery }}" placeholder="Search title, minister, or scripture">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <div class="sermon-library-filters" role="tablist" aria-label="Sermon type">
                <a class="sermon-library-filter{{ $activeType === '' ? ' is-active' : '' }}" href="{{ $filterUrl(null) }}">All</a>
                <a class="sermon-library-filter{{ $activeType === 'video' ? ' is-active' : '' }}" href="{{ $filterUrl('video') }}">Video</a>
                <a class="sermon-library-filter{{ $activeType === 'audio' ? ' is-active' : '' }}" href="{{ $filterUrl('audio') }}">Audio</a>
                <a class="sermon-library-filter{{ $activeType === 'notes' ? ' is-active' : '' }}" href="{{ $filterUrl('notes') }}">Notes</a>
            </div>
        </div>

        @if ($searchQuery !== '')
            <p class="sermon-library-hint">
                Showing results for <strong>{{ $searchQuery }}</strong>.
                <a href="{{ $filterUrl($activeType !== '' ? $activeType : null, '') }}">Clear search</a>
            </p>
        @endif

        @if ($items === [])
            <div class="sermon-library-empty">
                <div class="sermon-library-empty__icon" aria-hidden="true"><i class="fa fa-book"></i></div>
                <h3>Messages are being prepared</h3>
                <p>No published sermons match this view yet. Join us on Sunday{{ $sunday !== '' ? ' — '.$sunday : '' }} and check back here for the Word.</p>
                <a class="btn btn-primary" href="{{ route('public.contact') }}">Plan a visit</a>
            </div>
        @else
            @if ($featured)
                <article class="sermon-library-featured">
                    <a href="{{ $featured['url'] }}" class="sermon-library-featured__media">
                        <img src="{{ $featured['image_url'] }}" alt="{{ $featured['title'] }}">
                        @if (! empty($featured['has_video']))
                            <span class="sermon-library-play" aria-hidden="true"><i class="fa fa-play"></i></span>
                        @endif
                    </a>
                    <div class="sermon-library-featured__body">
                        <div class="sermon-library-chips">
                            <span class="sermon-library-chip sermon-library-chip--accent">Latest</span>
                            @if (! empty($featured['has_video']))<span class="sermon-library-chip">Video</span>@endif
                            @if (! empty($featured['has_audio']))<span class="sermon-library-chip">Audio</span>@endif
                            @if (! empty($featured['has_pdf']))<span class="sermon-library-chip">Notes</span>@endif
                        </div>
                        <h3><a href="{{ $featured['url'] }}">{{ $featured['title'] }}</a></h3>
                        <p class="sermon-library-meta">
                            @if (! empty($featured['date_display']))<span><i class="fa fa-calendar-alt"></i>{{ $featured['date_display'] }}</span>@endif
                            @if (! empty($featured['minister_name']))<span><i class="fa fa-user"></i>{{ $featured['minister_name'] }}</span>@endif
                            @if (! empty($featured['scripture_refs']))<span><i class="fa fa-book-open"></i>{{ $featured['scripture_refs'] }}</span>@endif
                        </p>
                        @if (! empty($featured['excerpt']))
                            <p class="sermon-library-excerpt">{{ $featured['excerpt'] }}</p>
                        @endif
                        <a class="btn btn-primary" href="{{ $featured['url'] }}">
                            @if (! empty($featured['has_video'])) Watch message
                            @elseif (! empty($featured['has_audio'])) Listen now
                            @else Read message
                            @endif
                        </a>
                    </div>
                </article>
            @endif

            @if ($grid !== [])
                <div class="row g-4">
                    @foreach ($grid as $item)
                        <div class="col-md-6 col-xl-4">
                            <article class="sermon-library-card h-100">
                                <a href="{{ $item['url'] }}" class="sermon-library-card__media">
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] }}">
                                    @if (! empty($item['has_video']))
                                        <span class="sermon-library-play sermon-library-play--sm" aria-hidden="true"><i class="fa fa-play"></i></span>
                                    @endif
                                </a>
                                <div class="sermon-library-card__body">
                                    <p class="sermon-library-meta">
                                        @if (! empty($item['date_display']))<span>{{ $item['date_display'] }}</span>@endif
                                        @if (! empty($item['minister_name']))<span>{{ $item['minister_name'] }}</span>@endif
                                    </p>
                                    <h3><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h3>
                                    @if (! empty($item['excerpt']))
                                        <p class="sermon-library-excerpt">{{ $item['excerpt'] }}</p>
                                    @endif
                                    <div class="sermon-library-card__footer">
                                        <div class="sermon-library-chips">
                                            @if (! empty($item['has_video']))<span class="sermon-library-chip">Video</span>@endif
                                            @if (! empty($item['has_audio']))<span class="sermon-library-chip">Audio</span>@endif
                                            @if (! empty($item['has_pdf']))<span class="sermon-library-chip">Notes</span>@endif
                                        </div>
                                        <a class="sermon-library-more" href="{{ $item['url'] }}">Open</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($pages > 1)
                <nav class="sermon-library-pager" aria-label="Sermon pages">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item{{ $pageNum <= 1 ? ' disabled' : '' }}">
                            @if ($pageNum <= 1)
                                <span class="page-link">&laquo;</span>
                            @else
                                <a class="page-link" href="{{ route('public.sermons', array_filter(['q' => $searchQuery ?: null, 'type' => $activeType ?: null, 'page' => $pageNum - 1])) }}" rel="prev">&laquo;</a>
                            @endif
                        </li>
                        @for ($p = 1; $p <= $pages; $p++)
                            <li class="page-item{{ $p === $pageNum ? ' active' : '' }}">
                                @if ($p === $pageNum)
                                    <span class="page-link">{{ $p }}</span>
                                @else
                                    <a class="page-link" href="{{ route('public.sermons', array_filter(['q' => $searchQuery ?: null, 'type' => $activeType ?: null, 'page' => $p])) }}">{{ $p }}</a>
                                @endif
                            </li>
                        @endfor
                        <li class="page-item{{ $pageNum >= $pages ? ' disabled' : '' }}">
                            @if ($pageNum >= $pages)
                                <span class="page-link">&raquo;</span>
                            @else
                                <a class="page-link" href="{{ route('public.sermons', array_filter(['q' => $searchQuery ?: null, 'type' => $activeType ?: null, 'page' => $pageNum + 1])) }}" rel="next">&raquo;</a>
                            @endif
                        </li>
                    </ul>
                    <p class="sermon-library-count">{{ $total }} message{{ $total === 1 ? '' : 's' }}</p>
                </nav>
            @endif
        @endif

        <div class="sermon-library-visit">
            <div>
                <h3>Hear the Word with us</h3>
                <p>Sunday worship{{ $sunday !== '' ? ' — '.$sunday : ' at AGC Ikenegbu' }}. Everyone is welcome.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('public.contact') }}">Plan a visit</a>
        </div>
    </div>
</section>

@include('public.partials.footer')
@endsection
