@extends('layouts.public')

@section('content')
@include('public.partials.site-chrome', ['navActive' => ''])

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <p class="fs-5 text-uppercase text-primary mb-2">404</p>
                <h1 class="display-5 mb-3">This page could not be found</h1>
                <p class="lead text-muted mb-4">The link may be outdated or the page may have moved. Try the homepage, blog, or contact page.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('public.home') }}" class="btn btn-primary">Go to Homepage</a>
                    <a href="{{ route('public.blog') }}" class="btn btn-outline-primary">Browse Blog</a>
                    <a href="{{ route('public.contact') }}" class="btn btn-outline-primary">Contact Us</a>
                    <a href="{{ route('public.sitemap') }}" class="btn btn-outline-primary">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection
