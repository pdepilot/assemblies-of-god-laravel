@extends('layouts.public')

@php
    $shortName = (string) ($church['short_name'] ?? config('identity.public.short_name', 'AGC-Ikenegbu'));
    $categories = is_array($categories ?? null) ? $categories : [];
    $featuredCampaign = is_array($featuredCampaign ?? null) ? $featuredCampaign : null;
    $campaignProgressBarWidth = ($featuredCampaign
        ? min(100, max(0, (float) ($featuredCampaign['progress_pct'] ?? 0)))
        : 0).'%';
    $paystackEnabled = (bool) ($paystackEnabled ?? false);
    $flutterwaveEnabled = (bool) ($flutterwaveEnabled ?? false);
    $paystackKey = (string) ($paystackKey ?? '');
    $flutterwaveKey = (string) ($flutterwaveKey ?? '');
    $page = is_array($page ?? null) ? $page : [];
    $copy = static function (string $key, string $fallback) use ($page): string {
        $value = trim((string) ($page[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    };
    $heroTitleHtml = nl2br(e($copy('hero_title', "Give Cheerfully.\nTransform Lives Eternally.")), false);
@endphp

@push('head')
    <link rel="preconnect" href="https://api.paystack.co" crossorigin>
    <link rel="preconnect" href="https://api.flutterwave.com" crossorigin>
    <link rel="preconnect" href="https://js.paystack.co" crossorigin>
    <link rel="preconnect" href="https://checkout.paystack.com" crossorigin>
    <link rel="preconnect" href="https://checkout.flutterwave.com" crossorigin>
    <link rel="preload" href="https://js.paystack.co/v1/inline.js" as="script">
    <link href="{{ asset('site/css/donate.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('public.partials.site-chrome', ['navActive' => 'donate'])

<main>
    <section class="donate-hero" aria-labelledby="donateHeroTitle">
        <div class="donate-hero__bg" aria-hidden="true"></div>
        <div class="donate-hero__overlay" aria-hidden="true"></div>
        <div class="donate-hero__content donate-reveal is-visible">
            <span class="donate-hero__badge">{{ $copy('hero_badge', 'Generosity · Faith · Impact') }}</span>
            <h1 id="donateHeroTitle" class="donate-hero__title">{!! $heroTitleHtml !!}</h1>
            <p class="donate-hero__scripture">{{ $copy('hero_scripture', '"Every man according as he purposeth in his heart, so let him give; not grudgingly, or of necessity: for God loveth a cheerful giver."') }}</p>
            <p class="donate-hero__ref">{{ $copy('hero_ref', '— 2 Corinthians 9:7 (KJV)') }}</p>
            <a href="#give-categories" class="donate-hero__cta">
                <i class="fas fa-hand-holding-heart" aria-hidden="true"></i>
                {{ $copy('hero_cta_label', 'Give Now') }}
            </a>
        </div>
        <a href="#give-categories" class="donate-hero__scroll" aria-label="Scroll to giving options"><i class="fas fa-chevron-down"></i></a>
    </section>

    <section class="donate-section" id="give-categories" aria-labelledby="categoriesTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('categories_eyebrow', 'Choose Your Gift') }}</span>
                    <h2 id="categoriesTitle" class="donate-section__title">{{ $copy('categories_title', 'Where Would You Like to Give?') }}</h2>
                    <p class="donate-section__lead">{{ $copy('categories_lead', 'Select a category below to continue to secure online giving with Paystack or Flutterwave. Your seed sows into souls, structures, and service.') }}</p>
                </header>

            <div class="donate-categories donate-reveal" role="group" aria-label="Donation categories">
                <button type="button" class="donate-category-card" data-category-slug="offering" aria-pressed="false">
                    <div class="donate-category-card__icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3 class="donate-category-card__title">Offering</h3>
                    <p class="donate-category-card__text">Worship God with a freewill gift of gratitude and faith.</p>
                    <span class="donate-category-card__action">Give online <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </button>
                <button type="button" class="donate-category-card" data-category-slug="tithe" aria-pressed="false">
                    <div class="donate-category-card__icon"><i class="fas fa-percent"></i></div>
                    <h3 class="donate-category-card__title">Tithes</h3>
                    <p class="donate-category-card__text">Honor the Lord with the firstfruits of your increase.</p>
                    <span class="donate-category-card__action">Give online <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </button>
                <button type="button" class="donate-category-card" data-category-slug="charity" aria-pressed="false">
                    <div class="donate-category-card__icon"><i class="fas fa-heart"></i></div>
                    <h3 class="donate-category-card__title">Charity</h3>
                    <p class="donate-category-card__text">Feed the hungry, clothe the needy, and bless the poor.</p>
                    <span class="donate-category-card__action">Give online <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </button>
                <button type="button" class="donate-category-card" data-category-slug="building_fund" aria-pressed="false">
                    <div class="donate-category-card__icon"><i class="fas fa-church"></i></div>
                    <h3 class="donate-category-card__title">Building / Project Fund</h3>
                    <p class="donate-category-card__text">Build the house of God and expand His kingdom reach.</p>
                    <span class="donate-category-card__action">Give online <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </button>
            </div>
        </div>
    </section>

    <section class="donate-section donate-section--alt" id="give-online" aria-labelledby="onlineGiveTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('online_eyebrow', 'Secure Online Giving') }}</span>
                    <h2 id="onlineGiveTitle" class="donate-section__title">{{ $copy('online_title', 'Give Online Instantly') }}</h2>
                    <p class="donate-section__lead">{{ $copy('online_lead', 'Pay securely with Paystack or Flutterwave. Your gift is recorded automatically — no manual approval needed.') }}</p>
                </header>
            <form id="donateOnlineForm" class="donate-online-form donate-reveal" novalidate>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlineDonorName">Full Name</label>
                        <input type="text" class="form-control donate-online-input" id="onlineDonorName" name="donor_name" required autocomplete="name" placeholder="John Okonkwo">
                    </div>
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlineDonorEmail">Email</label>
                        <input type="email" class="form-control donate-online-input" id="onlineDonorEmail" name="donor_email" required autocomplete="email" placeholder="you@email.com">
                    </div>
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlineDonorPhone">Phone</label>
                        <input type="tel" class="form-control donate-online-input" id="onlineDonorPhone" name="donor_phone" required autocomplete="tel" placeholder="08012345678">
                    </div>
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlineAmount">Amount (₦)</label>
                        <input type="number" class="form-control donate-online-input" id="onlineAmount" name="amount" min="100" step="1" required placeholder="5000">
                    </div>
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlineCategory">Donation Category</label>
                        <select class="form-select donate-online-input donate-online-select" id="onlineCategory" name="category_id" required>
                            <option value="">Select category…</option>
                            @foreach ($categories as $cat)
                                <option value="{{ (int) ($cat['id'] ?? 0) }}">{{ $cat['name'] ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="donate-online-label" for="onlinePurpose">Gift Purpose <small>(optional)</small></label>
                        <select class="form-select donate-online-input donate-online-select" id="onlinePurpose" name="purpose_id">
                            <option value="">General Giving</option>
                        </select>
                    </div>
                    <div class="col-12" id="dedicationFields" hidden>
                        <div class="row g-3 donate-dedication-panel">
                            <div class="col-md-6" id="dedicateeNameWrap">
                                <label class="donate-online-label" for="dedicateeName">Dedicated To / Honoree Name</label>
                                <input type="text" class="form-control donate-online-input" id="dedicateeName" name="dedicatee_name" placeholder="Mary Okafor">
                            </div>
                            <div class="col-md-6" id="dedicateeRelationshipWrap">
                                <label class="donate-online-label" for="dedicateeRelationship">Relationship <small>(optional)</small></label>
                                <input type="text" class="form-control donate-online-input" id="dedicateeRelationship" name="dedicatee_relationship" placeholder="Mother, Pastor, Friend…">
                            </div>
                            <div class="col-12">
                                <label class="donate-online-label" for="dedicationMessage">Personal Message</label>
                                <textarea class="form-control donate-online-input" id="dedicationMessage" name="dedication_message" rows="2" placeholder="Thanking God for another year of life."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" @unless ($paystackEnabled || $flutterwaveEnabled) hidden @endunless>
                        <label class="donate-online-label" for="onlineGateway">Payment Gateway</label>
                        <select class="form-select donate-online-input donate-online-select" id="onlineGateway" name="payment_provider">
                            @if ($paystackEnabled && $paystackKey !== '')
                                <option value="paystack">Paystack</option>
                            @endif
                            @if ($flutterwaveEnabled && $flutterwaveKey !== '')
                                <option value="flutterwave">Flutterwave</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-12">
                        <span class="donate-online-label d-block mb-2">Donation Type</span>
                        <div class="donate-type-toggle" role="radiogroup" aria-label="Donation type">
                            <label class="donate-type-option">
                                <input type="radio" name="donation_type" value="one_time" checked>
                                <span>One-Time Donation</span>
                            </label>
                            <label class="donate-type-option">
                                <input type="radio" name="donation_type" value="recurring">
                                <span>Recurring Donation</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12 donate-recurring-fields" id="recurringFields" hidden>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="donate-online-label" for="recurringFrequency">Frequency</label>
                                <select class="form-select donate-online-input" id="recurringFrequency" name="frequency">
                                    <option value="weekly">Weekly</option>
                                    <option value="bi_weekly">Bi-Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="donate-online-label" for="recurringStart">Start Date</label>
                                <input type="date" class="form-control donate-online-input" id="recurringStart" name="start_date">
                            </div>
                            <div class="col-md-4">
                                <label class="donate-online-label" for="recurringEnd">End Date <small>(optional)</small></label>
                                <input type="date" class="form-control donate-online-input" id="recurringEnd" name="end_date">
                            </div>
                            <div class="col-md-4">
                                <label class="donate-online-label" for="recurringOccurrences">Number of Occurrences <small>(optional)</small></label>
                                <input type="number" class="form-control donate-online-input" id="recurringOccurrences" name="max_occurrences" min="1" step="1">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="donate-online-check">
                            <input type="checkbox" id="onlineAnonymous" name="is_anonymous" value="1">
                            <span>Donate Anonymously <small>(your name will show as Anonymous Donor publicly)</small></span>
                        </label>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="donate-hero__cta donate-online-submit" id="onlineDonateBtn">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            Donate Securely
                        </button>
                        <p class="donate-online-note" id="donateOnlineStatus" role="status" aria-live="polite"></p>
                        <p class="donate-online-note">
                            <a href="{{ url('/my-giving') }}">Manage your recurring giving</a>
                            ·
                            <a href="{{ url('/giving-history') }}">View giving history</a>
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="donate-section" id="pledge-section" aria-labelledby="pledgeTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('pledge_eyebrow', 'Commitment Giving') }}</span>
                    <h2 id="pledgeTitle" class="donate-section__title">{{ $copy('pledge_title', 'Make a Building Fund Pledge') }}</h2>
                    <p class="donate-section__lead">{{ $copy('pledge_lead', 'Pledge a gift over time and track your progress toward completion.') }}</p>
                </header>
            <form id="donatePledgeForm" class="donate-online-form donate-reveal" novalidate>
                <div class="row g-4">
                    <div class="col-md-4"><label class="donate-online-label" for="pledgeName">Full Name</label><input type="text" class="form-control donate-online-input" id="pledgeName" name="donor_name" required></div>
                    <div class="col-md-4"><label class="donate-online-label" for="pledgeEmail">Email</label><input type="email" class="form-control donate-online-input" id="pledgeEmail" name="donor_email"></div>
                    <div class="col-md-4"><label class="donate-online-label" for="pledgePhone">Phone</label><input type="tel" class="form-control donate-online-input" id="pledgePhone" name="donor_phone"></div>
                    <div class="col-md-4"><label class="donate-online-label" for="pledgeAmount">Pledge Amount (₦)</label><input type="number" class="form-control donate-online-input" id="pledgeAmount" name="pledged_amount" min="1000" required></div>
                    <div class="col-md-4"><label class="donate-online-label" for="pledgeMonths">Duration (Months)</label><input type="number" class="form-control donate-online-input" id="pledgeMonths" name="installment_count" min="1" value="10"></div>
                    <div class="col-md-4"><label class="donate-online-label" for="pledgeStart">Start Date</label><input type="date" class="form-control donate-online-input" id="pledgeStart" name="start_date"></div>
                    <div class="col-12"><button type="submit" class="donate-hero__cta donate-online-submit"><i class="fas fa-handshake"></i> Submit Pledge</button><p class="donate-online-note" id="pledgeFormStatus" role="status"></p></div>
                </div>
            </form>
        </div>
    </section>

    <section class="donate-section donate-section--alt" id="sponsorship-section" aria-labelledby="sponsorshipTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('sponsorship_eyebrow', 'Kingdom Partnership') }}</span>
                    <h2 id="sponsorshipTitle" class="donate-section__title">{{ $copy('sponsorship_title', 'Become a Sponsor') }}</h2>
                    <p class="donate-section__lead">{{ $copy('sponsorship_lead', 'Support a child, missionary, student, widow, or community outreach project with a monthly commitment.') }}</p>
                </header>
            <form id="donateSponsorshipForm" class="donate-online-form donate-reveal" novalidate>
                <div class="row g-4">
                    <div class="col-md-4"><label class="donate-online-label" for="sponsorName">Your Name</label><input type="text" class="form-control donate-online-input" id="sponsorName" name="sponsor_name" required></div>
                    <div class="col-md-4"><label class="donate-online-label" for="sponsorEmail">Email</label><input type="email" class="form-control donate-online-input" id="sponsorEmail" name="sponsor_email"></div>
                    <div class="col-md-4"><label class="donate-online-label" for="sponsorPhone">Phone</label><input type="tel" class="form-control donate-online-input" id="sponsorPhone" name="sponsor_phone"></div>
                    <div class="col-md-4">
                        <label class="donate-online-label" for="sponsorshipType">Sponsorship Type</label>
                        <select class="form-select donate-online-input" id="sponsorshipType" name="sponsorship_type" required>
                            <option value="child">Child Sponsorship</option>
                            <option value="missionary">Missionary Sponsorship</option>
                            <option value="student">Student Sponsorship</option>
                            <option value="widow">Widow Support</option>
                            <option value="community_outreach">Community Outreach</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="donate-online-label" for="beneficiaryName">Beneficiary Name</label><input type="text" class="form-control donate-online-input" id="beneficiaryName" name="beneficiary_name" required></div>
                    <div class="col-md-4"><label class="donate-online-label" for="sponsorMonthly">Monthly Amount (₦)</label><input type="number" class="form-control donate-online-input" id="sponsorMonthly" name="monthly_amount" min="1000" required></div>
                    <div class="col-md-4"><label class="donate-online-label" for="sponsorDuration">Duration (Months)</label><input type="number" class="form-control donate-online-input" id="sponsorDuration" name="duration_months" min="1" placeholder="Optional"></div>
                    <div class="col-12"><button type="submit" class="donate-hero__cta donate-online-submit"><i class="fas fa-hands-helping"></i> Register Sponsorship</button><p class="donate-online-note" id="sponsorshipFormStatus" role="status"></p></div>
                </div>
            </form>
        </div>
    </section>

    @if ($featuredCampaign)
        <section class="donate-section" id="campaign-tracker" aria-labelledby="campaignTitle">
            <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">Fundraising Goal</span>
                    <h2 id="campaignTitle" class="donate-section__title">{{ $featuredCampaign['title'] !== '' ? $featuredCampaign['title'] : 'Campaign Progress' }}</h2>
                </header>
                <div class="donate-campaign donate-reveal" id="donateCampaignProgress">
                    <div class="donate-campaign__top">
                        <div class="donate-campaign__heading">
                            <span class="donate-campaign__eyebrow">Featured Campaign</span>
                            <h3 class="donate-campaign__name" id="campaignTitleName">{{ $featuredCampaign['title'] ?? '' }}</h3>
                        </div>
                        <span class="donate-campaign__pct" id="campaignProgressPct">{{ $featuredCampaign['progress_pct'] ?? 0 }}%</span>
                    </div>
                    <div class="donate-campaign__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) ($featuredCampaign['progress_pct'] ?? 0) }}" id="campaignProgressBarWrap">
                        <div class="donate-campaign__fill" id="campaignProgressBar" {!! 'style="width: '.e($campaignProgressBarWidth).'"' !!}></div>
                    </div>
                    <div class="donate-campaign__meta">
                        <div class="donate-campaign__stat"><span>Target</span><strong id="campaignTarget">₦{{ number_format((float) ($featuredCampaign['target_amount'] ?? 0), 0) }}</strong></div>
                        <div class="donate-campaign__stat"><span>Raised</span><strong id="campaignRaised">₦{{ number_format((float) ($featuredCampaign['raised_amount'] ?? 0), 0) }}</strong></div>
                        <div class="donate-campaign__stat"><span>Remaining</span><strong id="campaignRemaining">₦{{ number_format((float) ($featuredCampaign['remaining_amount'] ?? 0), 0) }}</strong></div>
                        <div class="donate-campaign__stat"><span>Donors</span><strong id="campaignDonors">{{ (int) ($featuredCampaign['donor_count'] ?? 0) }}</strong></div>
                        <div class="donate-campaign__stat"><span>Progress</span><strong id="campaignProgressLabel">{{ $featuredCampaign['progress_pct'] ?? 0 }}%</strong></div>
                        <div class="donate-campaign__stat"><span>Time Left</span><strong id="campaignCountdown">
                            @if (isset($featuredCampaign['days_remaining']))
                                {{ (int) $featuredCampaign['days_remaining'] }} {{ (int) $featuredCampaign['days_remaining'] === 1 ? 'day' : 'days' }}
                            @elseif (! empty($featuredCampaign['end_date']))
                                {{ $featuredCampaign['end_date'] }}
                            @else
                                Ongoing
                            @endif
                        </strong></div>
                    </div>
                </div>
                <div class="donate-campaigns-grid donate-reveal mt-4" id="campaignsGrid" aria-live="polite"></div>
            </div>
        </section>
    @else
        <section class="donate-section" id="campaign-tracker" hidden aria-hidden="true">
            <div class="container">
                <div class="donate-campaign" id="donateCampaignProgress" hidden></div>
                <div class="donate-campaigns-grid mt-4" id="campaignsGrid" aria-live="polite"></div>
            </div>
        </section>
    @endif

    <section class="donate-section donate-section--alt" aria-labelledby="trustTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('trust_eyebrow', 'Your Trust Matters') }}</span>
                    <h2 id="trustTitle" class="donate-section__title">{{ $copy('trust_title', 'Give With Confidence') }}</h2>
                </header>
            <div class="donate-trust donate-reveal">
                <div class="donate-trust-item">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    <div>
                        <h4>Secure Giving</h4>
                        <p>Official church accounts only. Always verify details on this page before transfer.</p>
                    </div>
                </div>
                <div class="donate-trust-item">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    <div>
                        <h4>Transparent Accounting</h4>
                        <p>Every gift is recorded, stewarded, and reported with integrity before God and our members.</p>
                    </div>
                </div>
                <div class="donate-trust-item">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <div>
                        <h4>Trusted by Members</h4>
                        <p>Thousands of faithful partners support our worship, outreach, and missions each year.</p>
                    </div>
                </div>
                <div class="donate-trust-item">
                    <i class="fas fa-globe-africa" aria-hidden="true"></i>
                    <div>
                        <h4>International Donations Accepted</h4>
                        <p>Partners worldwide can give via SWIFT transfer to our designated accounts.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="donate-section" aria-labelledby="impactTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('impact_eyebrow', 'Your Impact') }}</span>
                    <h2 id="impactTitle" class="donate-section__title">{{ $copy('impact_title', 'How Your Giving Makes a Difference') }}</h2>
                    <p class="donate-section__lead">{{ $copy('impact_lead', 'Every naira and every dollar fuels Gospel work — from the pew to the nations.') }}</p>
                </header>
            <div class="donate-stats donate-reveal">
                <div class="donate-stat">
                    <div class="donate-stat__icon"><i class="fas fa-hands-helping"></i></div>
                    <span class="donate-stat__num" data-count="1200" data-suffix="+">0</span>
                    <span class="donate-stat__label">Community Outreach</span>
                </div>
                <div class="donate-stat">
                    <div class="donate-stat__icon"><i class="fas fa-gift"></i></div>
                    <span class="donate-stat__num" data-count="850" data-suffix="+">0</span>
                    <span class="donate-stat__label">Charity Programs</span>
                </div>
                <div class="donate-stat">
                    <div class="donate-stat__icon"><i class="fas fa-hard-hat"></i></div>
                    <span class="donate-stat__num" data-count="15" data-suffix="+">0</span>
                    <span class="donate-stat__label">Church Projects</span>
                </div>
                <div class="donate-stat">
                    <div class="donate-stat__icon"><i class="fas fa-child"></i></div>
                    <span class="donate-stat__num" data-count="600" data-suffix="+">0</span>
                    <span class="donate-stat__label">Youth Development</span>
                </div>
                <div class="donate-stat">
                    <div class="donate-stat__icon"><i class="fas fa-cross"></i></div>
                    <span class="donate-stat__num" data-count="40" data-suffix="+">0</span>
                    <span class="donate-stat__label">Missions &amp; Evangelism</span>
                </div>
            </div>
        </div>
    </section>

    <section class="donate-section donate-section--alt" aria-labelledby="donorsTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal">
                    <span class="donate-section__eyebrow">{{ $copy('donors_eyebrow', 'Faithful Partners') }}</span>
                    <h2 id="donorsTitle" class="donate-section__title">{{ $copy('donors_title', 'Recent Donors') }}</h2>
                    <p class="donate-section__lead">{{ $copy('donors_lead', "Celebrating those who sow into God's work. Phone numbers show the first half only for privacy.") }}</p>
                </header>
            <div class="donate-donors-wrap donate-reveal">
                <table class="donate-donors-table">
                    <thead>
                        <tr>
                            <th scope="col">Donor Name</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Category</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Time</th>
                        </tr>
                    </thead>
                    <tbody id="donorTableBody"></tbody>
                </table>
            </div>
            <div class="donate-live-pagination donate-reveal" id="donorPagination">
                <button type="button" class="donate-live-pagination__btn" id="donorPrev" disabled aria-label="Previous donors page"><i class="fas fa-chevron-left"></i> Previous</button>
                <span class="donate-live-pagination__info" id="donorPageInfo">Page 1 of 1</span>
                <button type="button" class="donate-live-pagination__btn" id="donorNext" disabled aria-label="Next donors page">Next <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <section class="donate-section" aria-labelledby="donorTestimoniesTitle">
        <div class="container">
                <header class="donate-section__header donate-reveal donate-giver-stories__header">
                    <span class="donate-section__eyebrow">{{ $copy('stories_eyebrow', 'Why We Give') }}</span>
                    <h2 id="donorTestimoniesTitle" class="donate-section__title">{{ $copy('stories_title', 'Stories From Our Givers') }}</h2>
                    <p class="donate-section__lead mx-auto">{{ $copy('stories_lead', 'Real hearts. Real faith. Real impact — told in the words of those who sow.') }}</p>
                <button type="button" class="donate-giver-story-btn" data-bs-toggle="modal" data-bs-target="#agGivingStoryModal">
                    <span class="donate-giver-story-btn__icon" aria-hidden="true"><i class="fas fa-feather-alt"></i></span>
                    <span class="donate-giver-story-btn__text">
                        <strong>Share Why You Gave</strong>
                        <small>Your story may inspire another cheerful giver</small>
                    </span>
                    <span class="donate-giver-story-btn__arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                </button>
            </header>
            <div class="donate-testimonies donate-reveal" id="donateGiverStories">
                <blockquote class="donate-testimony-card">
                    <p>Giving here taught me that generosity is worship. When I support outreach, I see hungry families fed and souls saved — and my faith grows stronger.</p>
                    <cite>Adaeze Okafor<span>Monthly Tithe Partner</span></cite>
                </blockquote>
            </div>
        </div>
    </section>

    <section class="donate-final-cta donate-reveal" aria-labelledby="finalCtaTitle">
        <div class="donate-final-cta__inner">
            <h2 id="finalCtaTitle">{{ $copy('final_cta_title', "Your Gift Today Becomes Someone's Tomorrow") }}</h2>
            <p>{{ $copy('final_cta_text', 'Join a community of cheerful givers advancing the Gospel in Owerri and beyond. Select a category above and give securely online today.') }}</p>
            <a href="#give-categories" class="donate-hero__cta">
                <i class="fas fa-seedling" aria-hidden="true"></i>
                {{ $copy('final_cta_label', 'Give Your Best Seed') }}
            </a>
        </div>
    </section>
</main>

<section class="seo-faq container" aria-label="FAQ">
    <h2 class="seo-faq__title">Frequently Asked Questions</h2>
    <details class="seo-faq__item">
        <summary>How can I donate to {{ $shortName }}?</summary>
        <p class="seo-faq__answer">You can give online through our secure Give page using bank transfer or supported payment methods. Tithes, offerings, charity, and building fund gifts are welcome.</p>
    </details>
    <details class="seo-faq__item">
        <summary>Are international donations accepted?</summary>
        <p class="seo-faq__answer">Yes. {{ $shortName }} accepts donations from partners in Nigeria and abroad. Contact us for international transfer details if needed.</p>
    </details>
</section>

@include('public.partials.footer')
@endsection

@push('scripts')
    <script src="{{ asset('site/js/giving-story-form.js') }}" defer></script>
    <script src="{{ asset('site/js/donate.js') }}" defer></script>
@endpush
