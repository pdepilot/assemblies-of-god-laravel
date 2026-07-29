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
                @include('public.partials.nav', ['navActive' => $navActive ?? ''])
                <div class="navbar-cta-group align-items-center flex-shrink-0">
                    <a href="{{ route('public.sdtg') }}" class="btn btn-sdtg-nav"><i class="fas fa-globe-africa" aria-hidden="true"></i><span>Send Down Thy Glory</span></a>
                    <a href="{{ route('public.donate') }}" class="btn btn-primary py-2 px-4">Give</a>
                </div>
            </div>
        </nav>
    </div>
</div>
