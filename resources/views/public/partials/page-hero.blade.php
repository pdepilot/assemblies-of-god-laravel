{{-- Original AG site inner-page hero (hero-header). Do not use page-header — that class has no CSS. --}}
@php
    $title = (string) ($heroTitle ?? 'Page');
    $homeLabel = (string) ($breadcrumbHome ?? 'Home');
    $parentLabel = trim((string) ($breadcrumbParent ?? 'Pages'));
    $parentUrl = trim((string) ($breadcrumbParentUrl ?? '#'));
    $currentLabel = (string) ($breadcrumbCurrent ?? $title);
    $bgUrl = trim((string) ($heroUrl ?? ''));
@endphp
<div
    class="container-fluid hero-header"
    @if ($bgUrl !== '')
        style="background-image:linear-gradient(rgba(26, 43, 92, 0.55), rgba(26, 43, 92, 0.45)), url('{{ $bgUrl }}');background-size:cover;background-position:center;"
    @endif
>
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="hero-header-inner animated zoomIn">
                    <h1 class="display-1 text-dark">{{ $title }}</h1>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ $homeLabel }}</a></li>
                        @if ($parentLabel !== '')
                            <li class="breadcrumb-item">
                                @if ($parentUrl !== '' && $parentUrl !== '#')
                                    <a href="{{ $parentUrl }}">{{ $parentLabel }}</a>
                                @else
                                    <a href="#">{{ $parentLabel }}</a>
                                @endif
                            </li>
                        @endif
                        <li class="breadcrumb-item text-dark" aria-current="page">{{ $currentLabel }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
