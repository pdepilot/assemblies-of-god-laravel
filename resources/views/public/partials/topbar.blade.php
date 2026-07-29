@php
    $social = $church['social'] ?? [];
@endphp
<div class="topbar-grid">
    <div class="topbar-contact">
        <a href="tel:{{ $church['phone_tel'] }}" class="topbar-link">
            <i class="fa fa-phone-alt" aria-hidden="true"></i>
            <span>{{ $church['phone_display'] }}</span>
        </a>
        <a href="mailto:{{ $church['email'] }}" class="topbar-link">
            <i class="far fa-envelope" aria-hidden="true"></i>
            <span>{{ $church['email'] }}</span>
        </a>
    </div>
    <div class="topbar-social">
        <a href="{{ route('public.member-portal.login') }}" class="topbar-member-login">
            <i class="fas fa-user-circle" aria-hidden="true"></i>
            <span>Member Login</span>
        </a>
        <span class="topbar-social-label">Follow Us:</span>
        <div class="topbar-social-icons">
            <a href="{{ $social['facebook'] ?? '#' }}" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="{{ $social['twitter'] ?? '#' }}" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="{{ $social['linkedin'] ?? '#' }}" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="{{ $social['instagram'] ?? '#' }}" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</div>
