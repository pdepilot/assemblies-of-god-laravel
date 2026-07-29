@php
    // Gallery images are already resolved to absolute URLs by PublicHomepageReadService.
    $gallery = array_values(array_filter(
        is_array($about['gallery'] ?? null) ? $about['gallery'] : [],
        static fn ($item): bool => is_array($item) && trim((string) ($item['image'] ?? '')) !== ''
    ));
    while (count($gallery) < 3) {
        $gallery[] = ['image' => app(\App\Services\PublicSite\PublicAssetResolver::class)->url('images/main1.jpg'), 'alt' => 'AG Ikenebgu church'];
    }
    $gallery = array_slice($gallery, 0, 3);
    $highlight = is_array($about['highlight'] ?? null) ? $about['highlight'] : [];
    $features = array_values(array_filter(
        is_array($about['features'] ?? null) ? $about['features'] : [],
        static fn ($item): bool => trim((string) $item) !== ''
    ));
    $mid = (int) ceil(count($features) / 2);
    $leftFeatures = array_slice($features, 0, $mid);
    $rightFeatures = array_slice($features, $mid);
@endphp
<div class="container-fluid about py-5">
    <div class="container py-5">
        <div class="row g-5 mb-5">
            <div class="col-xl-6">
                <div class="row g-4 about-gallery">
                    <div class="col-6">
                        <img src="{{ $gallery[0]['image'] }}" class="img-fluid h-100 wow zoomIn" data-wow-delay="0.1s" alt="{{ $gallery[0]['alt'] ?? '' }}">
                    </div>
                    <div class="col-6">
                        <img src="{{ $gallery[1]['image'] }}" class="img-fluid pb-3 wow zoomIn" data-wow-delay="0.1s" alt="{{ $gallery[1]['alt'] ?? '' }}">
                        <img src="{{ $gallery[2]['image'] }}" class="img-fluid pt-3 wow zoomIn" data-wow-delay="0.1s" alt="{{ $gallery[2]['alt'] ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="col-xl-6 wow fadeIn" data-wow-delay="0.5s">
                <p class="fs-5 text-uppercase text-primary">{{ $about['eyebrow'] ?? '' }}</p>
                <h1 class="display-5 pb-4 m-0">{{ $about['title'] ?? '' }}</h1>
                <p class="pb-4">{{ $about['intro'] ?? '' }}</p>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="ps-3 d-flex align-items-center justify-content-start">
                            <span class="bg-primary btn-md-square rounded-circle mt-4 me-2"><i class="fa fa-eye text-dark fa-4x mb-5 pb-2"></i></span>
                            <div class="ms-4">
                                <h5>{{ $about['vision_title'] ?? 'Our Vision' }}</h5>
                                <p>{{ $about['vision_text'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ps-3 d-flex align-items-center justify-content-start">
                            <span class="bg-primary btn-md-square rounded-circle mt-4 me-2"><i class="fa fa-flag text-dark fa-4x mb-5 pb-2"></i></span>
                            <div class="ms-4">
                                <h5>{{ $about['mission_title'] ?? 'Our Mission' }}</h5>
                                <p>{{ $about['mission_text'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @if (! empty($highlight['quote']) || ! empty($highlight['stat_value']))
                    <div class="bg-light p-3 mb-4">
                        <div class="row align-items-center justify-content-center">
                            @if (! empty($highlight['image']))
                                <div class="col-3">
                                    <img src="{{ $highlight['image'] }}" class="img-fluid rounded-circle" alt="{{ $highlight['image_alt'] ?? '' }}">
                                </div>
                            @endif
                            <div class="col-6">
                                <p class="mb-0">{{ $highlight['quote'] ?? '' }}</p>
                            </div>
                            @if (! empty($highlight['stat_value']))
                                <div class="col-3">
                                    <h2 class="mb-0 text-primary text-center">{{ $highlight['stat_value'] }}</h2>
                                    <h5 class="mb-0 text-center">{{ $highlight['stat_label'] ?? '' }}</h5>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                @if ($leftFeatures || $rightFeatures)
                    <div class="row g-2">
                        <div class="col-md-6">
                            @foreach ($leftFeatures as $feature)
                                <p class="mb-2"><i class="fa fa-check text-primary me-3"></i>{{ $feature }}</p>
                            @endforeach
                        </div>
                        <div class="col-md-6">
                            @foreach ($rightFeatures as $i => $feature)
                                <p class="{{ $i === count($rightFeatures) - 1 ? 'mb-0' : 'mb-2' }}"><i class="fa fa-check text-primary me-3"></i>{{ $feature }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
