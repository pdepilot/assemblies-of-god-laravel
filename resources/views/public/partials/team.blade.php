@php
    $settings = $team['settings'] ?? [];
    $featured = $team['featured'] ?? null;
    $members = $team['members'] ?? [];
    $title = $settings['title'] ?? 'Meet Our Church Leadership';
    $eyebrow = $settings['eyebrow'] ?? 'Our Team';
@endphp
@if ($featured || count($members) > 0)
<div class="container-fluid team py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5 wow fadeIn" data-wow-delay="0.1s" style="max-width: 700px;">
            <p class="fs-5 text-uppercase text-primary">{{ $eyebrow }}</p>
            <h1 class="display-3">{{ $title }}</h1>
        </div>
        <div class="row g-5">
            @if ($featured)
                <div class="col-lg-4 col-xl-5">
                    <div class="team-img wow zoomIn" data-wow-delay="0.1s">
                        @if (! empty($featured['photo_url']))
                            <img src="{{ $featured['photo_url'] }}" class="img-fluid" alt="{{ $featured['full_name'] ?? '' }}">
                        @endif
                    </div>
                </div>
                <div class="col-lg-8 col-xl-7">
                    <div class="team-item wow fadeIn" data-wow-delay="0.1s">
                        <h1>{{ $featured['full_name'] ?? '' }}</h1>
                        @if (! empty($featured['role_title']))
                            <h5 class="fw-normal fst-italic text-primary mb-4">{{ $featured['role_title'] }}</h5>
                        @endif
                        @if (! empty($featured['bio']))
                            <p class="mb-4">{{ $featured['bio'] }}</p>
                        @endif
                        <div class="team-icon d-flex pb-4 mb-4 border-bottom border-primary">
                            <a class="btn btn-primary btn-lg-square me-2" href="{{ $featured['social_facebook'] ?? '#' }}"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-primary btn-lg-square me-2" href="{{ $featured['social_twitter'] ?? '#' }}"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-primary btn-lg-square me-2" href="{{ $featured['social_instagram'] ?? '#' }}"><i class="fab fa-instagram"></i></a>
                            <a class="btn btn-primary btn-lg-square me-2" href="{{ $featured['social_linkedin'] ?? '#' }}"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    @if (count($members) > 0)
                        <div class="row g-4">
                            @foreach ($members as $i => $member)
                                <div class="col-md-6">
                                    <div class="team-item wow fadeIn" data-wow-delay="{{ 0.2 + ($i * 0.1) }}s">
                                        @if (! empty($member['photo_url']))
                                            <img src="{{ $member['photo_url'] }}" class="img-fluid mb-3" alt="{{ $member['full_name'] ?? '' }}">
                                        @endif
                                        <h4>{{ $member['full_name'] ?? '' }}</h4>
                                        @if (! empty($member['role_title']))
                                            <p class="text-primary mb-0">{{ $member['role_title'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="col-12">
                    <div class="row g-4">
                        @foreach ($members as $i => $member)
                            <div class="col-md-6 col-lg-4">
                                <div class="team-item wow fadeIn" data-wow-delay="{{ 0.1 + ($i * 0.1) }}s">
                                    @if (! empty($member['photo_url']))
                                        <img src="{{ $member['photo_url'] }}" class="img-fluid mb-3" alt="{{ $member['full_name'] ?? '' }}">
                                    @endif
                                    <h4>{{ $member['full_name'] ?? '' }}</h4>
                                    @if (! empty($member['role_title']))
                                        <p class="text-primary mb-0">{{ $member['role_title'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
