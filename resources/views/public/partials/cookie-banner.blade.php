<div id="agCookieBreak" class="ag-cookie-break" aria-hidden="true">
    <div class="ag-cookie-break__flash" aria-hidden="true"></div>
    <div class="ag-cookie-break__cracks" aria-hidden="true">
        <span class="ag-cookie-break__crack ag-cookie-break__crack--1"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--2"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--3"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--4"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--5"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--6"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--7"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--8"></span>
        <span class="ag-cookie-break__crack ag-cookie-break__crack--9"></span>
    </div>
</div>

<div id="agCookieBanner" class="ag-cookie-banner" role="dialog" aria-labelledby="agCookieTitle" aria-describedby="agCookieDesc" aria-hidden="true">
    <div class="ag-cookie-banner__border" aria-hidden="true">
        <div class="ag-cookie-banner__border-rotate"></div>
    </div>
    <div class="ag-cookie-banner__border-glow" aria-hidden="true"></div>
    <div class="ag-cookie-banner__inner">
        <div class="ag-cookie-banner__seal" aria-hidden="true">
            <i class="fas fa-church" aria-hidden="true"></i>
        </div>
        <div class="ag-cookie-banner__content">
            <p class="ag-cookie-banner__eyebrow">A Warm Welcome</p>
            <h2 id="agCookieTitle" class="ag-cookie-banner__title">Your Privacy, Our Covenant</h2>
            <p id="agCookieDesc" class="ag-cookie-banner__text">{{ config('identity.public.short_name', 'AGC Ikenegbu') }} uses essential cookies so the site works. With your permission we also use analytics and Google AdSense advertising cookies. Read our <a href="{{ route('public.privacy') }}">Privacy Policy</a> and <a href="{{ route('public.cookie-policy') }}">Cookie Policy</a>.</p>
            <div class="ag-cookie-banner__actions">
                <button type="button" class="ag-cookie-banner__btn ag-cookie-banner__btn--accept" data-action="accept-all">
                    <i class="fas fa-check-circle" aria-hidden="true"></i> Accept All
                </button>
                <button type="button" class="ag-cookie-banner__btn ag-cookie-banner__btn--essential" data-action="essential-only">Essential Only</button>
                <button type="button" class="ag-cookie-banner__btn ag-cookie-banner__btn--prefs" data-action="customize">
                    <i class="fas fa-sliders-h" aria-hidden="true"></i> Preferences
                </button>
            </div>
        </div>
    </div>
</div>

<div id="agCookieModal" class="ag-cookie-modal" role="dialog" aria-modal="true" aria-labelledby="agCookieModalTitle" aria-hidden="true">
    <div class="ag-cookie-modal__panel">
        <div class="ag-cookie-modal__border" aria-hidden="true">
            <div class="ag-cookie-modal__border-rotate"></div>
        </div>
        <div class="ag-cookie-modal__inner">
            <button type="button" class="ag-cookie-modal__close" data-action="close-modal" aria-label="Close preferences">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <h2 id="agCookieModalTitle" class="ag-cookie-modal__title">Cookie Preferences</h2>
            <p class="ag-cookie-modal__desc">Choose how {{ config('identity.public.short_name', 'AGC Ikenegbu') }} may use cookies. Essential cookies are always active so the site works properly.</p>
            <div class="ag-cookie-pref">
                <div class="ag-cookie-pref__info">
                    <h4>Essential</h4>
                    <p>Required for core site functionality and security.</p>
                    <span class="ag-cookie-pref__tag">Always Active</span>
                </div>
                <label class="ag-cookie-toggle" aria-label="Essential cookies always enabled">
                    <input type="checkbox" data-ag-cookie-pref="essential" checked disabled>
                    <span class="ag-cookie-toggle__track"></span>
                </label>
            </div>
            <div class="ag-cookie-pref">
                <div class="ag-cookie-pref__info">
                    <h4>Analytics</h4>
                    <p>Google Analytics 4 helps us understand how visitors use our ministry website after you consent. It does not receive your email address from newsletter forms.</p>
                </div>
                <label class="ag-cookie-toggle">
                    <input type="checkbox" data-ag-cookie-pref="analytics" id="agCookieAnalytics">
                    <span class="ag-cookie-toggle__track"></span>
                </label>
            </div>
            <div class="ag-cookie-pref">
                <div class="ag-cookie-pref__info">
                    <h4>Performance</h4>
                    <p>Improves speed and reliability across pages.</p>
                </div>
                <label class="ag-cookie-toggle">
                    <input type="checkbox" data-ag-cookie-pref="performance" id="agCookiePerformance">
                    <span class="ag-cookie-toggle__track"></span>
                </label>
            </div>
            <div class="ag-cookie-pref">
                <div class="ag-cookie-pref__info">
                    <h4>Advertising</h4>
                    <p>Google AdSense and partners may use cookies to serve ads based on visits to this site and other sites. You can opt out in Google Ads Settings. Analytics cookies never load ads by themselves.</p>
                </div>
                <label class="ag-cookie-toggle">
                    <input type="checkbox" data-ag-cookie-pref="personalization" id="agCookiePersonalization">
                    <span class="ag-cookie-toggle__track"></span>
                </label>
            </div>
            <div class="ag-cookie-modal__actions">
                <button type="button" class="ag-cookie-banner__btn ag-cookie-banner__btn--accept" data-action="save-prefs">Save Preferences</button>
                <button type="button" class="ag-cookie-banner__btn ag-cookie-banner__btn--essential" data-action="accept-all-modal">Accept All</button>
            </div>
        </div>
    </div>
</div>
