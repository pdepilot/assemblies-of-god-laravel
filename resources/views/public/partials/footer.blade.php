@php
    $social = $church['social'] ?? [];
    $publicIdentity = config('identity.public');
    $fallbackShortName = (string) ($publicIdentity['short_name'] ?? 'AGC Ikenegbu');
    $nameParts = preg_split('/\s+/', trim(($church['short_name'] ?? '') !== '' ? $church['short_name'] : ($church['church_name'] ?? $fallbackShortName))) ?: ['AG', 'Ikenebgu'];
    $brandLead = (string) ($nameParts[0] ?? 'AG');
    $brandRest = trim(implode(' ', array_slice($nameParts, 1)));
    if ($brandRest === '') {
        $brandRest = 'Ikenebgu';
    }
    $logoVideoUrl = asset('site/'.ltrim((string) ($publicIdentity['logo_video_path'] ?? 'videos/Create_a_cinematic_D_animatio.mp4'), '/'));
@endphp
<footer class="container-fluid ag-footer pt-0 wow fadeIn" data-wow-delay="0.1s">
    <div class="footer-blessing">
        <div class="footer-glow footer-glow-1"></div>
        <div class="footer-glow footer-glow-2"></div>
        <div class="container position-relative">
            <div class="row justify-content-center py-5">
                <div class="col-lg-10 col-xl-8 text-center">
                    <a href="{{ url('/') }}" class="footer-logo-link d-inline-block mb-4">
                        <div class="ag-logo-video ag-logo-video--footer" aria-label="{{ $church['church_name'] ?? ($publicIdentity['site_name'] ?? $fallbackShortName) }} logo">
                            <video class="ag-logo-video__el" src="{{ $logoVideoUrl }}" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
                        </div>
                    </a>
                    <p class="footer-verse mb-3">"The Lord bless thee, and keep thee: the Lord make his face shine upon thee, and be gracious unto thee: the Lord lift up his countenance upon thee, and give thee peace."</p>
                    <p class="footer-verse-ref mb-4">— Numbers 6:24–26 (KJV)</p>
                    <h2 class="footer-blessing-title display-6 text-light mb-3">May Heaven Pour Down Blessings Upon You</h2>
                    <p class="footer-blessing-sub mb-0">From our church family to yours — grace, peace, and favor in Christ Jesus.</p>
                </div>
            </div>
        </div>
        <svg class="footer-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,40 C240,80 480,0 720,40 C960,80 1200,0 1440,40 L1440,80 L0,80 Z"></path>
        </svg>
    </div>
    <div class="footer-body">
        <div class="container py-5">
            <div class="row g-5 align-items-start">
                <div class="col-lg-4">
                    <div class="footer-item">
                        <h4 class="text-light mb-4">{{ $brandLead }} <span class="text-primary">{{ $brandRest }}</span></h4>
                        <p class="footer-text mb-4">A spirit-filled Assemblies of God church proclaiming the full Gospel — where worship, fellowship, and compassion meet every heart.</p>
                        <div class="footer-social d-flex gap-2">
                            <a class="footer-social-btn" href="{{ $social['facebook'] ?? '#' }}" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a class="footer-social-btn" href="{{ $social['instagram'] ?? '#' }}" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a class="footer-social-btn" href="{{ $social['youtube'] ?? '#' }}" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                            <a class="footer-social-btn" href="{{ $social['whatsapp'] ?? '#' }}" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="footer-item">
                        <h4 class="text-light mb-4">Worship With Us</h4>
                        <ul class="footer-list list-unstyled mb-0">
                            <li><i class="fa fa-church text-primary me-2"></i>Sunday — {{ $church['sunday_worship'] }}</li>
                            <li><i class="fa fa-bible text-primary me-2"></i>{{ $church['midweek_service'] }}</li>
                            <li><i class="fa fa-praying-hands text-primary me-2"></i>{{ $church['prayer_meeting'] }}</li>
                        </ul>
                        <a href="{{ route('public.contact') }}" class="btn btn-primary mt-4 py-2 px-4">Plan Your Visit</a>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="footer-item">
                        <h4 class="text-light mb-4">Reach Us</h4>
                        <div class="footer-contact">
                            <p class="mb-3"><i class="fa fa-map-marker-alt text-primary me-2"></i>{{ $church['address_full'] }}</p>
                            <p class="mb-3"><i class="fa fa-phone-alt text-primary me-2"></i><a href="tel:{{ $church['phone_tel'] }}">{{ $church['phone_display'] }}</a></p>
                            <p class="mb-0"><i class="far fa-envelope text-primary me-2"></i><a href="mailto:{{ $church['email'] }}">{{ $church['email'] }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-5 pt-4 border-top border-secondary border-opacity-25">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <h4 class="text-light mb-3">Stay Connected</h4>
                    <p class="footer-text small mb-3">Get devotionals and church updates in your inbox.</p>
                    <form id="agFooterNewsletter" data-newsletter data-source="footer" data-api="{{ route('public.newsletter.subscribe') }}" class="footer-newsletter">
                        @csrf
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" placeholder="Your email" required aria-label="Email for newsletter">
                            <button type="submit" class="btn btn-primary">Subscribe</button>
                        </div>
                        <p class="small mt-2 mb-0" data-newsletter-status role="status"></p>
                    </form>
                </div>
                <div class="col-lg-7 text-lg-end">
                    <nav class="footer-legal mb-3" aria-label="Legal and site links">
                        <a href="{{ route('public.privacy') }}">Privacy</a>
                        <a href="{{ route('public.terms') }}">Terms</a>
                        <a href="{{ route('public.cookie-policy') }}">Cookie Policy</a>
                        <a href="{{ route('public.cookie-policy') }}" data-action="open-cookie-prefs">Cookie settings</a>
                        <a href="{{ route('public.disclaimer') }}">Disclaimer</a>
                        <a href="{{ route('public.about') }}">About</a>
                        <a href="{{ route('public.contact') }}">Contact</a>
                        <a href="{{ route('public.faq') }}">FAQ</a>
                        <a href="{{ route('public.leadership') }}">Leadership</a>
                        <a href="{{ route('public.statement-of-faith') }}">Statement of Faith</a>
                        <a href="{{ route('public.sitemap') }}">Sitemap</a>
                    </nav>
                    <p class="mb-1 text-light-50 small">&copy; {{ date('Y') }} {{ $church['church_name'] }}. All rights reserved.</p>
                    <p class="mb-0 text-light-50 small">Developed by <span class="text-primary">ERIBS Tech</span></p>
                </div>
            </div>
        </div>
    </div>
</footer>
