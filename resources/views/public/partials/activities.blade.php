@php
    $wowDelays = ['0.1s', '0.3s', '0.5s'];
@endphp
<div class="row g-4">
    @forelse ($activities as $index => $activity)
        @php
            $icon = $iconClass((string) ($activity['icon_class'] ?? 'fa-church'));
            $url = trim((string) ($activity['read_more_url'] ?? ''));
            if ($url !== '' && ! preg_match('#^(https?:)?//#i', $url) && ! str_starts_with($url, '/')) {
                $url = rtrim($legacy_base, '/').'/'.ltrim($url, '/');
            }
        @endphp
        <div class="col-lg-6 col-xl-4">
            <div class="activities-item p-4 wow fadeIn" data-wow-delay="{{ $wowDelays[$index % count($wowDelays)] }}">
                <i class="fa {{ $icon }} fa-4x text-dark"></i>
                <div class="ms-4">
                    <h4>{{ $activity['title'] ?? '' }}</h4>
                    <p class="mb-4">{{ $activity['description'] ?? '' }}</p>
                    @if ($url !== '')
                        <a href="{{ $url }}" class="btn btn-primary px-3">Read More</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center text-muted py-5">
            <p>Ministry activities will appear here soon. Please check back.</p>
        </div>
    @endforelse
</div>
