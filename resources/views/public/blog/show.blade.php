@extends('layouts.public')

@section('content')
<div class="container-fluid fixed-top">
    <div class="container topbar">
        <div class="topbar-inner">
            @include('public.partials.topbar')
        </div>
    </div>
    <div class="container">
        <nav class="navbar navbar-light navbar-expand-lg py-3">
            <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
                <div class="ag-logo-video ag-logo-video--nav me-2" aria-label="AG Ikenebgu 3D logo">
                    <video class="ag-logo-video__el" src="{{ asset('site/videos/Create_a_cinematic_D_animatio.mp4') }}" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
                </div>
                <span class="mb-0 lh-sm"><strong class="text-dark">AG</strong><span class="text-primary"> Ikenebgu</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-label="Toggle navigation">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                @include('public.partials.nav', ['navActive' => 'blog'])
                <div class="navbar-cta-group align-items-center flex-shrink-0">
                    <a href="{{ route('public.sdtg') }}" class="btn btn-sdtg-nav"><i class="fas fa-globe-africa" aria-hidden="true"></i><span>Send Down Thy Glory</span></a>
                    <a href="{{ route('public.donate') }}" class="btn btn-primary py-2 px-4">Give</a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="container-fluid page-header py-5">
    <div class="container text-center py-5">
        <h1 class="display-5 text-white mb-3 animated slideInDown">{{ $post['title'] }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('public.blog') }}">Blog</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">{{ \Illuminate\Support\Str::limit($post['title'], 40) }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <img src="{{ $post['image_url'] }}" class="img-fluid rounded mb-4 w-100" style="max-height:420px;object-fit:cover" alt="{{ $post['title'] }}">
                <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                    <span>{{ $post['category_label'] }}</span>
                    @if ($post['published_display'] !== '')
                        <span>{{ $post['published_display'] }}</span>
                    @endif
                    @if (! empty($post['author']))
                        <span>By {{ $post['author'] }}</span>
                    @endif
                </div>
                @if (! empty($post['excerpt']))
                    <p class="lead">{{ $post['excerpt'] }}</p>
                @endif
                <div class="blog-body">
                    {!! $post['body_html'] !!}
                </div>
                <div class="mt-5">
                    <a href="{{ route('public.blog') }}" class="btn btn-outline-primary">Back to blog</a>
                </div>
            </div>
        </div>
    </div>
</div>

@include('public.partials.footer')
@endsection
