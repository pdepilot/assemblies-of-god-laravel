<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $portal['event_name'] }} | Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('site/css/registration-portal.css') }}">
    <link rel="icon" href="{{ asset('site/'.ltrim((string) config('identity.public.logo_path', 'images/ag-logo.jpeg'), '/')) }}" type="image/jpeg">
</head>
<body
    class="rp-public"
    data-portal-slug="{{ $slug }}"
    data-api-base="{{ url('/api') }}"
    @if ($isAdminPreview) data-admin-preview="1" @endif
>
@if ($isAdminPreview)
    <div class="rp-public__draft-banner" role="status">
        <strong>Draft preview</strong> — This portal is not published. Visitors will not see this page until you publish it in admin.
        <a href="{{ route('registration-portals.show', $portal['id']) }}" style="margin-left:12px;color:inherit">Back to admin</a>
    </div>
@endif

@php
    $bannerUrl = trim((string) ($portal['banner_url'] ?? ''));
    $heroStyleAttr = $bannerUrl !== ''
        ? ' style="background-image:url(\''.e($bannerUrl).'\')"'
        : '';
@endphp
<header class="rp-public__hero"{!! $heroStyleAttr !!}>
    <div class="rp-public__hero-overlay"></div>
    <div class="rp-public__hero-inner">
        <img src="{{ asset('site/'.ltrim((string) config('identity.public.logo_path', 'images/ag-logo.jpeg'), '/')) }}" alt="{{ config('identity.public.short_name', 'AGC Ikenegbu') }}" class="rp-public__logo">
        <p class="rp-public__eyebrow">{{ $portal['category'] ?: 'Event Registration' }}</p>
        <h1>{{ ($landing['hero_title'] ?? '') ?: $portal['event_name'] }}</h1>
        @if (!empty($landing['hero_subtitle']) || !empty($portal['event_subtitle']))
            <p class="rp-public__subtitle">{{ ($landing['hero_subtitle'] ?? '') ?: $portal['event_subtitle'] }}</p>
        @endif
        @if (!empty($portal['start_date']))
            <div class="rp-public__countdown" id="eventCountdown" data-date="{{ \Illuminate\Support\Str::of((string) $portal['start_date'])->before(' ') }}"></div>
        @endif
    </div>
</header>

<main class="rp-public__main">
    <section class="rp-public__card">
        <h2>Register Now</h2>
        @if (! $canRegister)
            <p class="rp-public__notice">Registration is currently closed for this event.</p>
        @elseif ($isAdminPreview && ($portal['status'] ?? '') !== 'open')
            <p class="rp-public__notice">Preview mode — submissions are disabled until this portal is published.</p>
            @include('registration-portals._public-fields', ['fields' => $fields, 'disabled' => true])
        @else
            <form id="publicRegistrationForm" class="rp-public__form" enctype="multipart/form-data">
                @include('registration-portals._public-fields', ['fields' => $fields, 'disabled' => false])
                <button type="submit" class="rp-public__submit">Complete Registration</button>
            </form>
            <div id="registrationSuccess" class="rp-public__success" hidden></div>
        @endif
    </section>

    @if (!empty($landing['about_enabled']) && !empty($landing['about_content']))
        <section class="rp-public__card">
            <h2>About This Event</h2>
            <div>{!! $landing['about_content'] !!}</div>
        </section>
    @endif

    @if (!empty($portal['venue']) || !empty($portal['map_link']))
        <section class="rp-public__card">
            <h2>Venue</h2>
            <p>{{ $portal['venue'] }}</p>
            @if (!empty($portal['map_link']))
                <a href="{{ $portal['map_link'] }}" target="_blank" rel="noopener">View on Google Maps</a>
            @endif
        </section>
    @endif
</main>

<footer class="rp-public__footer">
    <p>{{ ($landing['footer_text'] ?? '') ?: config('identity.public.site_name', 'AGC Ikenegbu Assemblies of God') }}</p>
    @if (!empty($portal['contact_email']))
        <p><a href="mailto:{{ $portal['contact_email'] }}">{{ $portal['contact_email'] }}</a></p>
    @endif
</footer>

<script src="{{ asset('site/js/registration-portal-public.js') }}"></script>
</body>
</html>
