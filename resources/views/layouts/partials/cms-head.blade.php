@php
    /** @var array<string, mixed> $cmsBootData */
    $shellPlatform = $shellPlatform ?? \App\Support\RbacPlatform::AG;
    $activePage = $activePage ?? 'dashboard';
    $isSundaySchool = $isSundaySchool ?? false;
    $isSsLegacyShell = $isSsLegacyShell ?? false;
    $isSettingsShell = $isSettingsShell ?? false;
    $isCmsNativeShell = $isCmsNativeShell ?? false;
    $portalCss = asset('portal/css');
    $identity = $cmsBootData['identity'] ?? [];
    $pageTitleSuffix = $identity['page_title_suffix'] ?? 'AGC IKENEGBU';
    $faviconUrl = $cmsBootData['favicon_url'] ?? '';
    $bodyPlatformClass = ' cms-app--ag';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#070b16">
    <title>{{ $title ?? 'Dashboard' }} | {{ $pageTitleSuffix }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ $portalCss }}/main.css?v={{ is_file(public_path('portal/css/main.css')) ? filemtime(public_path('portal/css/main.css')) : 1 }}">
    <link rel="stylesheet" href="{{ $portalCss }}/laravel-overrides.css?v={{ is_file(public_path('portal/css/laravel-overrides.css')) ? filemtime(public_path('portal/css/laravel-overrides.css')) : 1 }}">
    @if ($isCmsNativeShell)
        @vite(['resources/js/app.js'])
        @if ($isSsLegacyShell)
            <link rel="stylesheet" href="{{ $portalCss }}/sunday-school.css">
        @endif
    @elseif ($isSundaySchool)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ $portalCss }}/sunday-school.css">
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="icon" href="{{ $faviconUrl }}" type="image/jpeg">
    @stack('head')
</head>
<body
    class="cms-app{{ $isSundaySchool ? ' cms-app--sunday-school' : '' }}{{ $bodyPlatformClass }}{{ ($admin->ui_mode ?? 'dark') === 'dark' ? ' dark' : '' }}"
    data-page="{{ $isSettingsShell ? 'settings' : ($isSundaySchool ? 'sunday-school' : $activePage) }}"
    data-platform="{{ $shellPlatform }}"
    data-depth="0"
    data-theme="{{ $admin->ui_theme ?? 'gold' }}"
    data-mode="{{ $admin->ui_mode ?? 'dark' }}"
>
