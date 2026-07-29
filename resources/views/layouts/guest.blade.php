@php
    $mediaBase = config('portal.media_base');
    $portalCss = asset('portal/css');
    $portalJs = asset('portal/js');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0a0f1f">
    <title>Admin Login | AGC Ikenebgu & SDTG Management Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ $portalCss }}/auth.css">
    <link rel="stylesheet" href="{{ $portalCss }}/admin-auth.css">
    <link rel="icon" href="{{ $mediaBase }}/images/ag-logo.jpeg" type="image/jpeg">
</head>
<body class="auth-page">
    <a href="#authForm" class="auth-skip">Skip to login form</a>

    <div class="auth-bg" aria-hidden="true">
        <div class="auth-bg__grid"></div>
        <div class="auth-bg__orb auth-bg__orb--1"></div>
        <div class="auth-bg__orb auth-bg__orb--2"></div>
        <div class="auth-bg__orb auth-bg__orb--3"></div>
        <span class="auth-bg__trail auth-bg__trail--1"></span>
        <span class="auth-bg__trail auth-bg__trail--2"></span>
        <span class="auth-bg__trail auth-bg__trail--3"></span>
        <canvas id="authParticles" aria-hidden="true"></canvas>
    </div>

    <div id="authOverlay" class="auth-overlay" role="alertdialog" aria-labelledby="authOverlayTitle" aria-describedby="authOverlayStatus" aria-hidden="true">
        <div class="auth-overlay__shield" aria-hidden="true"><i class="fas fa-shield-halved"></i></div>
        <p id="authOverlayTitle" class="auth-overlay__title">Secure Authentication</p>
        <div class="auth-overlay__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div id="authProgressBar" class="auth-overlay__progress-bar"></div>
        </div>
        <p id="authOverlayStatus" class="auth-overlay__status">Signing in…</p>
    </div>

    <main class="auth-shell">
        <section class="auth-panel auth-panel--visual" aria-label="Platform overview">
            <header class="auth-brand auth-glass" style="padding: 24px;">
                <div class="auth-brand__logos">
                    <div class="auth-logo-video auth-logo-video--ag" aria-label="AGC Ikenebgu 3D logo">
                        <video class="auth-logo-video__el" src="{{ $mediaBase }}/videos/Create_a_cinematic_D_animatio.mp4" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
                    </div>
                    <div class="auth-logo-video auth-logo-video--sdtg" aria-label="Send Down Thy Glory 3D logo">
                        <video class="auth-logo-video__el" src="{{ $mediaBase }}/sdgt/videos/3d-logo.mp4" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
                    </div>
                </div>
                <div class="auth-brand__titles">
                    <h1>AGC IKENEGBU</h1>
                    <p>Church Management Platform</p>
                </div>
                <div class="auth-brand__divider" aria-hidden="true"></div>
                <p class="auth-brand__sdtg">Send Down Thy Glory</p>
                <p style="font-size: 0.82rem; color: var(--auth-text-muted); margin-top: 4px;">Event Management Platform</p>
            </header>
            <div class="auth-security" role="list" aria-label="Security features">
                <article class="auth-security__card auth-glass" role="listitem">
                    <span class="auth-security__icon auth-security__icon--gold" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
                    <div class="auth-security__text"><strong>Enterprise Security</strong><span>Argon2id password hashing</span></div>
                </article>
                <article class="auth-security__card auth-glass" role="listitem">
                    <span class="auth-security__icon" aria-hidden="true"><i class="fas fa-user-lock"></i></span>
                    <div class="auth-security__text"><strong>Brute-Force Protection</strong><span>Device fingerprint blocking</span></div>
                </article>
                <article class="auth-security__card auth-glass" role="listitem">
                    <span class="auth-security__icon auth-security__icon--gold" aria-hidden="true"><i class="fas fa-lock"></i></span>
                    <div class="auth-security__text"><strong>CSRF &amp; Session Security</strong><span>HttpOnly · SameSite cookies</span></div>
                </article>
            </div>
        </section>

        <section class="auth-panel auth-panel--login" aria-label="Secure login">
            <div class="auth-login-card auth-glass">
                {{ $slot }}
            </div>
        </section>
    </main>

    <script src="{{ $portalJs }}/laravel-login.js" defer></script>
</body>
</html>
