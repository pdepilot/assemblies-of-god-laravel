@php
    $banner = is_array($promotionBanner ?? null) ? $promotionBanner : null;
@endphp
@if ($banner && trim((string) ($banner['title'] ?? '')) !== '')
    @php
        $ctaLabel = trim((string) ($banner['cta_label'] ?? ''));
        $ctaHref = trim((string) ($banner['cta_href'] ?? ''));
        $imageUrl = trim((string) ($banner['image_url'] ?? ''));
        $eyebrow = trim((string) ($banner['eyebrow'] ?? ''));
        $body = trim((string) ($banner['body'] ?? ''));
        $schedule = trim((string) ($banner['schedule_label'] ?? ''));
        $showEveryVisit = ! empty($banner['show_every_visit']);
        $dismissKey = (string) ($banner['dismiss_key'] ?? '');
        $title = (string) ($banner['title'] ?? '');
    @endphp
    <template id="agPromoDismissTpl" hidden data-key="{{ $dismissKey }}"></template>
    <script>
        (function () {
            try {
                var node = document.getElementById('agPromoDismissTpl');
                var key = node ? node.getAttribute('data-key') : '';
                if (key && (window.sessionStorage.getItem(key) || window.localStorage.getItem(key))) {
                    document.documentElement.classList.add('ag-promo-dismissed');
                }
            } catch (e) {}
        })();
    </script>
    <div
        id="agPromoBanner"
        class="ag-promo"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="agPromoTitle"
        data-dismiss-key="{{ $dismissKey }}"
        data-show-every-visit="{{ $showEveryVisit ? '1' : '0' }}"
    >
        <div class="ag-promo__backdrop" data-ag-promo-close></div>
        <div class="ag-promo__card">
            <button type="button" class="ag-promo__close" data-ag-promo-close aria-label="Close promotion">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            @if ($imageUrl !== '')
                <div class="ag-promo__media">
                    <img src="{{ $imageUrl }}" alt="" class="ag-promo__img">
                </div>
            @endif
            <div class="ag-promo__body">
                @if ($eyebrow !== '')
                    <p class="ag-promo__eyebrow">{{ $eyebrow }}</p>
                @endif
                <h2 id="agPromoTitle" class="ag-promo__title">{{ $title }}</h2>
                @if ($schedule !== '')
                    <p class="ag-promo__when">{{ $schedule }}</p>
                @endif
                @if ($body !== '')
                    <p class="ag-promo__text">{{ $body }}</p>
                @endif
                @if ($ctaLabel !== '' && $ctaHref !== '')
                    <a href="{{ $ctaHref }}" class="btn btn-primary ag-promo__cta">{{ $ctaLabel }}</a>
                @endif
            </div>
        </div>
    </div>
@endif
