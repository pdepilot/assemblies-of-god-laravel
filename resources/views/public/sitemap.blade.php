@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => ''])

@include('public.partials.page-hero', [
    'heroTitle' => 'Sitemap',
    'breadcrumbCurrent' => 'Sitemap',
])

<div class="container-fluid py-5">
    <div class="container py-5">
        <p class="text-muted mb-4">Find public pages, articles, and sermons on this site.</p>
        <div class="row g-4">
            @foreach ($groups as $group => $items)
                <div class="col-md-6 col-lg-4">
                    <h2 class="h5 text-primary">{{ $group }}</h2>
                    <ul class="list-unstyled">
                        @foreach ($items as $item)
                            <li class="mb-2"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection
