@php
    $cards = $sermons['cards'] ?? [];
    $delays = ['0.1s', '0.3s', '0.5s'];
@endphp
@if (count($cards) > 0)
<div class="container-fluid sermon py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5 wow fadeIn" data-wow-delay="0.1s" style="max-width: 700px;">
            <p class="fs-5 text-uppercase text-primary">{{ $sermons['eyebrow'] ?? 'Sermons' }}</p>
            <h1 class="display-3">{{ $sermons['title'] ?? '' }}</h1>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach ($cards as $i => $card)
                <div class="col-lg-6 col-xl-4">
                    <div class="sermon-item wow fadeIn" data-wow-delay="{{ $delays[$i] ?? '0.1s' }}">
                        <div class="overflow-hidden p-4 pb-0">
                            <img src="{{ $card['image_url'] ?? asset('site/img/sermon-1.jpg') }}" class="img-fluid w-100" alt="{{ $card['title'] ?? 'Sermon' }}">
                        </div>
                        <div class="p-4">
                            <div class="sermon-meta d-flex justify-content-between pb-2">
                                <div>
                                    @if (! empty($card['date_label']))
                                        <small><i class="fa fa-calendar me-2 text-muted"></i><span class="text-muted me-2">{{ $card['date_label'] }}</span></small>
                                    @endif
                                    @if (! empty($card['author']))
                                        <small><i class="fas fa-user me-2 text-muted"></i><span class="text-muted">{{ $card['author'] }}</span></small>
                                    @endif
                                </div>
                                <div>
                                    @if (! empty($card['has_video']))
                                        <a href="{{ $card['link'] }}" class="me-1" aria-label="Watch video"><i class="fas fa-video text-muted"></i></a>
                                    @endif
                                    @if (! empty($card['has_audio']))
                                        <a href="{{ $card['link'] }}" class="me-1" aria-label="Listen to audio"><i class="fas fa-headphones text-muted"></i></a>
                                    @endif
                                    @if (! empty($card['has_pdf']))
                                        <a href="{{ $card['link'] }}" class="me-1" aria-label="Read notes"><i class="fas fa-file-alt text-muted"></i></a>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $card['link'] }}" class="h4 d-inline-block mb-3">{{ $card['title'] ?? '' }}</a>
                            <p class="mb-0">{{ $card['description'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
