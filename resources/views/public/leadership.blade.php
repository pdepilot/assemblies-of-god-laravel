@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'about'])

@include('public.partials.page-hero', [
    'heroTitle' => $page['heading'] ?? 'Leadership Team',
    'breadcrumbCurrent' => 'Leadership',
])

<div class="container-fluid py-5">
    <div class="container py-5">
        @if (! empty($page['intro']))
            <p class="lead text-center mb-5">{{ $page['intro'] }}</p>
        @endif
        @if (! empty($page['body_html']))
            <div class="mb-5">{!! $page['body_html'] !!}</div>
        @endif

        <div class="row g-4">
            @forelse ($members as $member)
                <div class="col-md-6 col-lg-4">
                    <article class="bg-light rounded h-100 p-4 text-center">
                        @if (! empty($member['photo_url'] ?? $member['photo_path'] ?? null))
                            @php
                                $photo = $member['photo_url'] ?? app(\App\Services\PublicSite\PublicAssetResolver::class)->url((string) ($member['photo_path'] ?? ''));
                            @endphp
                            <img src="{{ $photo }}" alt="{{ $member['full_name'] ?? '' }}" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover">
                        @endif
                        <h2 class="h5 mb-1">{{ $member['full_name'] ?? '' }}</h2>
                        <p class="text-primary mb-2">{{ $member['role_title'] ?? '' }}</p>
                        @if (! empty($member['bio']))
                            <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit(strip_tags((string) $member['bio']), 160) }}</p>
                        @endif
                    </article>
                </div>
            @empty
                <p class="text-muted text-center">Leadership profiles will appear here soon.</p>
            @endforelse
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection
