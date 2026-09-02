<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class WebsitePagesReadService
{
    /**
     * Catalog of frontend pages managed from Website → Page Manager.
     *
     * @return array<string, array{
     *   label: string,
     *   type: string,
     *   public_route: string|null,
     *   description: string,
     *   related: list<array{label: string, route: string, params?: array<string, mixed>}>
     * }>
     */
    public function pageCatalog(): array
    {
        return [
            'home' => [
                'label' => 'Homepage (Index)',
                'type' => 'home',
                'public_route' => 'public.home',
                'description' => 'Hero, section titles, and homepage copy for ministries, events, worship, sermons, team, and testimonies.',
                'related' => [
                    ['label' => 'Edit hero banner', 'route' => 'website.pages.hero.edit'],
                    ['label' => 'About section body', 'route' => 'website.about.edit', 'params' => ['tab' => 'homepage_about']],
                    ['label' => 'Team members', 'route' => 'website.team.index'],
                    ['label' => 'Blog posts', 'route' => 'website.blog.index'],
                    ['label' => 'Events list', 'route' => 'events.index'],
                    ['label' => 'Sermons library', 'route' => 'sermon.sermons.index'],
                ],
            ],
            'about' => [
                'label' => 'About Us',
                'type' => 'header',
                'public_route' => 'public.about',
                'description' => 'About page header plus vision, mission, gallery, and CTA content.',
                'related' => [
                    ['label' => 'About page body (vision, mission, gallery)', 'route' => 'website.about.edit', 'params' => ['tab' => 'about_page']],
                    ['label' => 'Team members', 'route' => 'website.team.index'],
                ],
            ],
            'activity' => [
                'label' => 'Ministries',
                'type' => 'header',
                'public_route' => 'public.activity',
                'description' => 'Ministries / activities page header and intro.',
                'related' => [],
            ],
            'event' => [
                'label' => 'Events',
                'type' => 'header',
                'public_route' => 'public.event',
                'description' => 'Events page header and intro. Event cards are managed under Events.',
                'related' => [
                    ['label' => 'Manage events', 'route' => 'events.index'],
                ],
            ],
            'blog' => [
                'label' => 'Blog',
                'type' => 'header',
                'public_route' => 'public.blog',
                'description' => 'Blog listing header. Individual posts are managed under Blog.',
                'related' => [
                    ['label' => 'Manage blog posts', 'route' => 'website.blog.index'],
                ],
            ],
            'sermons' => [
                'label' => 'Sermons',
                'type' => 'header',
                'public_route' => 'public.sermons',
                'description' => 'Sermons page header and intro. Sermon items are managed under Sermons.',
                'related' => [
                    ['label' => 'Manage sermons', 'route' => 'sermon.sermons.index'],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'type' => 'content',
                'public_route' => 'public.contact',
                'description' => 'Contact page header, intro, and body content.',
                'related' => [
                    ['label' => 'Contact submissions inbox', 'route' => 'contact.submissions.index'],
                ],
            ],
            'donate' => [
                'label' => 'Give / Donate',
                'type' => 'donate',
                'public_route' => 'public.donate',
                'description' => 'Full Give page copy: hero, section titles, and calls to action.',
                'related' => [
                    ['label' => 'Donation records', 'route' => 'donations.index'],
                ],
            ],
            'privacy' => [
                'label' => 'Privacy Policy',
                'type' => 'content',
                'public_route' => 'public.privacy',
                'description' => 'Privacy policy page content.',
                'related' => [],
            ],
            'terms' => [
                'label' => 'Terms of Use',
                'type' => 'content',
                'public_route' => 'public.terms',
                'description' => 'Terms of use page content.',
                'related' => [],
            ],
            'cookie-policy' => [
                'label' => 'Cookie Policy',
                'type' => 'content',
                'public_route' => 'public.cookie-policy',
                'description' => 'Cookie policy for consent and analytics preferences.',
                'related' => [],
            ],
            'statement-of-faith' => [
                'label' => 'Statement of Faith',
                'type' => 'content',
                'public_route' => 'public.statement-of-faith',
                'description' => 'Doctrinal statement of faith.',
                'related' => [],
            ],
            'mission-vision' => [
                'label' => 'Mission & Vision',
                'type' => 'content',
                'public_route' => 'public.mission-vision',
                'description' => 'Church mission and vision statements.',
                'related' => [],
            ],
            'editorial-policy' => [
                'label' => 'Editorial Policy',
                'type' => 'content',
                'public_route' => 'public.editorial-policy',
                'description' => 'Editorial standards for published Christian content.',
                'related' => [],
            ],
            'accessibility' => [
                'label' => 'Accessibility Statement',
                'type' => 'content',
                'public_route' => 'public.accessibility',
                'description' => 'Accessibility commitments for the public website.',
                'related' => [],
            ],
            'disclaimer' => [
                'label' => 'Disclaimer',
                'type' => 'content',
                'public_route' => 'public.disclaimer',
                'description' => 'Public website disclaimer.',
                'related' => [],
            ],
            'faq' => [
                'label' => 'Frequently Asked Questions',
                'type' => 'content',
                'public_route' => 'public.faq',
                'description' => 'Public FAQ content (use heading + paragraph pairs for FAQ schema).',
                'related' => [],
            ],
            'leadership' => [
                'label' => 'Leadership Team',
                'type' => 'content',
                'public_route' => 'public.leadership',
                'description' => 'Leadership page chrome. Members are managed under Team.',
                'related' => [
                    ['label' => 'Team members', 'route' => 'website.team.index'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getBootstrap(): array
    {
        $settings = $this->readSettings();
        $pages = [];
        foreach ($this->pageCatalog() as $key => $meta) {
            $pages[$key] = $this->getPage($key);
        }

        return [
            'ag' => [
                'hero' => $this->resolveAgHero($settings),
                'pages' => $pages,
            ],
            'custom_pages' => array_values($settings['custom_pages'] ?? []),
            'catalog' => $this->pageCatalog(),
        ];
    }

    /** @return array<string, mixed> */
    public function getPage(string $pageKey): array
    {
        $pageKey = strtolower(trim($pageKey));
        $catalog = $this->pageCatalog();
        if (! isset($catalog[$pageKey])) {
            return [];
        }

        $defaults = $this->defaultPageContent($pageKey);
        $settings = $this->readSettings();
        $stored = is_array($settings['ag']['pages'][$pageKey] ?? null)
            ? $settings['ag']['pages'][$pageKey]
            : [];

        $merged = array_replace_recursive($defaults, $stored);
        if (trim(strip_tags((string) ($merged['body_html'] ?? ''))) === '') {
            $fallback = $this->defaultLegalBody(
                $pageKey,
                (string) config('identity.public.short_name', 'AGC-Ikenegbu')
            );
            if ($fallback !== '') {
                $merged['body_html'] = $fallback;
            }
        }

        return $merged;
    }

    public function isEditablePage(string $pageKey): bool
    {
        return isset($this->pageCatalog()[strtolower(trim($pageKey))]);
    }

    /** @return array<string, mixed> */
    public function defaultPageContent(string $pageKey): array
    {
        $shortName = (string) config('identity.public.short_name', 'AGC-Ikenegbu');
        $meta = $this->pageCatalog()[$pageKey] ?? null;
        $type = is_array($meta) ? (string) $meta['type'] : 'content';
        $label = is_array($meta) ? (string) $meta['label'] : ucfirst($pageKey);

        return match ($type) {
            'home' => [
                'ministries_eyebrow' => 'Ministries',
                'ministries_title' => 'Serving God Through Every Season of Life',
                'events_eyebrow' => 'Gather With Us',
                'events_title' => 'Upcoming Events',
                'events_intro' => 'Worship, study, and prayer — rhythm of life together at '.$shortName.'. Mark your calendar and bring someone along.',
                'worship_eyebrow' => 'Plan Your Visit',
                'worship_title' => 'Join Us In Worship',
                'worship_intro' => 'Everyone is welcome. Come worship with us at '.$shortName.' - Ikenegbu Layout, Owerri.',
                'sermons_eyebrow' => 'Sermons',
                'sermons_title' => 'Latest Messages',
                'team_eyebrow' => 'Our Team',
                'team_title' => 'Meet Our Church Leadership',
                'testimonials_eyebrow' => 'Testimonials',
                'testimonials_title' => 'Stories of Faith From Our Church Family',
                'testimonials_intro' => 'Real stories of grace, healing, and transformation in Christ.',
            ],
            'header' => [
                'heading' => $label,
                'eyebrow' => '',
                'intro' => '',
                'hero_image' => '',
                'cta_label' => '',
                'cta_url' => '',
            ],
            'donate' => [
                'hero_badge' => 'Generosity · Faith · Impact',
                'hero_title' => "Give Cheerfully.\nTransform Lives Eternally.",
                'hero_scripture' => '"Every man according as he purposeth in his heart, so let him give; not grudgingly, or of necessity: for God loveth a cheerful giver."',
                'hero_ref' => '— 2 Corinthians 9:7 (KJV)',
                'hero_cta_label' => 'Give Now',
                'categories_eyebrow' => 'Choose Your Gift',
                'categories_title' => 'Where Would You Like to Give?',
                'categories_lead' => 'Select a category below to continue to secure online giving with Paystack or Flutterwave. Your seed sows into souls, structures, and service.',
                'online_eyebrow' => 'Secure Online Giving',
                'online_title' => 'Give Online Instantly',
                'online_lead' => 'Pay securely with Paystack or Flutterwave. Your gift is recorded automatically — no manual approval needed.',
                'pledge_eyebrow' => 'Commitment Giving',
                'pledge_title' => 'Make a Building Fund Pledge',
                'pledge_lead' => 'Pledge a gift over time and track your progress toward completion.',
                'sponsorship_eyebrow' => 'Kingdom Partnership',
                'sponsorship_title' => 'Become a Sponsor',
                'sponsorship_lead' => 'Support a child, missionary, student, widow, or community outreach project with a monthly commitment.',
                'trust_eyebrow' => 'Your Trust Matters',
                'trust_title' => 'Give With Confidence',
                'impact_eyebrow' => 'Your Impact',
                'impact_title' => 'How Your Giving Makes a Difference',
                'impact_lead' => 'Every naira and every dollar fuels Gospel work — from the pew to the nations.',
                'donors_eyebrow' => 'Faithful Partners',
                'donors_title' => 'Recent Donors',
                'donors_lead' => "Celebrating those who sow into God's work. Phone numbers show the first half only for privacy.",
                'stories_eyebrow' => 'Why We Give',
                'stories_title' => 'Stories From Our Givers',
                'stories_lead' => 'Real hearts. Real faith. Real impact — told in the words of those who sow.',
                'final_cta_title' => "Your Gift Today Becomes Someone's Tomorrow",
                'final_cta_text' => 'Join a community of cheerful givers advancing the Gospel in Owerri and beyond. Select a category above and give securely online today.',
                'final_cta_label' => 'Give Your Best Seed',
            ],
            default => [
                'heading' => $label,
                'eyebrow' => '',
                'intro' => '',
                'body_html' => $this->defaultLegalBody($pageKey, $shortName),
                'hero_image' => '',
                'cta_label' => '',
                'cta_url' => '',
            ],
        };
    }

    private function defaultLegalBody(string $pageKey, string $shortName): string
    {
        $name = htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) config('identity.email.from_email', 'info@agikenebgu.org'), ENT_QUOTES, 'UTF-8');
        $address = htmlspecialchars((string) config('identity.email.church_address', 'Ikenegbu, Owerri, Imo State, Nigeria'), ENT_QUOTES, 'UTF-8');

        return match ($pageKey) {
            'privacy' => $this->defaultPrivacyBody($name, $email, $address),
            'terms' => $this->defaultTermsBody($name, $email),
            'cookie-policy' => $this->defaultCookiePolicyBody($name),
            'statement-of-faith' => '<p>'.$name.' affirms the core doctrines of the Assemblies of God: the Bible as God\'s Word, salvation through Jesus Christ, the baptism in the Holy Spirit, divine healing, and the blessed hope of Christ\'s return.</p>',
            'mission-vision' => '<p><strong>Mission:</strong> To proclaim the full Gospel of Jesus Christ, make disciples, and serve our community in love.</p><p><strong>Vision:</strong> A Spirit-filled church family transforming lives in Owerri and beyond.</p>',
            'editorial-policy' => '<p>Content published on this website aims to edify believers, share the Gospel accurately, and respect the dignity of every reader. We review submissions for biblical fidelity, clarity, and pastoral care before publication.</p>',
            'accessibility' => '<p>We strive to make '.$name.'\'s public website usable for people of all abilities. If you encounter a barrier, please <a href="'.e(url('/contact')).'">contact us</a> so we can improve.</p>',
            'disclaimer' => '<p>Information on this website is provided for ministry and educational purposes. It is not a substitute for pastoral counsel, professional advice, or in-person fellowship.</p>',
            'faq' => '<h2>What time are Sunday services?</h2><p>Join us for Sunday worship — see our homepage or Contact page for current service times.</p><h2>How can I visit?</h2><p>Everyone is welcome. Use Plan Your Visit on the Contact page and we will gladly help you find us.</p><h2>How can I give?</h2><p>You can give securely online via our Give page, or in person during services.</p>',
            'leadership' => '<p>Meet the pastoral and ministry leaders serving '.$name.'.</p>',
            default => '',
        };
    }

    private function defaultPrivacyBody(string $name, string $email, string $address): string
    {
        $contact = e(url('/contact'));
        $cookies = e(url('/cookie-policy'));
        $terms = e(url('/terms'));

        return <<<HTML
<p>{$name} ("we", "us") operates this church website from {$address}. This privacy policy explains what information we collect, how we use it, and the choices you have. It applies to visitors of this public website.</p>
<h2>Who we are</h2>
<p>We are a local Assemblies of God congregation. For privacy questions, email <a href="mailto:{$email}">{$email}</a> or use our <a href="{$contact}">contact form</a>.</p>
<h2>Information we collect</h2>
<p>We may collect information you choose to give us, including your name, email address, phone number, and message when you contact us, subscribe to our newsletter, submit a testimony, register for an event, join as a member, or make a donation. We also collect limited technical data such as browser type, pages visited, and approximate location derived from IP address when you use the site.</p>
<h2>How we use information</h2>
<p>We use this information to respond to enquiries, send requested updates, administer ministry programmes, process giving, improve the website, keep the site secure, and (only with your consent) measure traffic and show advertising. We do not sell your personal information.</p>
<h2>Cookies and advertising</h2>
<p>We use cookies and similar technologies as described in our <a href="{$cookies}">Cookie Policy</a>. Essential cookies are required for the site to work. Optional analytics, performance, and advertising cookies run only after you choose them in our cookie banner.</p>
<p>Third-party vendors, including Google, use cookies to serve ads based on a user's prior visits to this website or other websites. Google's use of advertising cookies enables it and its partners to serve ads to our users based on their visit to this site and/or other sites on the Internet.</p>
<p>You may opt out of personalized advertising by visiting <a href="https://www.google.com/settings/ads" rel="noopener noreferrer" target="_blank">Google Ads Settings</a>. You can also opt out of a third-party vendor's use of cookies for personalized advertising at <a href="https://www.aboutads.info" rel="noopener noreferrer" target="_blank">www.aboutads.info</a>.</p>
<p>Learn how Google uses data when you use our partners' sites or apps at <a href="https://policies.google.com/technologies/partner-sites" rel="noopener noreferrer" target="_blank">How Google uses information from sites or apps that use our services</a>.</p>
<h2>Children</h2>
<p>This website is intended for a general audience and is not directed at children under 13. We do not knowingly collect personal information from children under 13 for advertising. If you believe a child has submitted personal information to us, please contact us and we will delete it.</p>
<h2>Sharing</h2>
<p>We share information with service providers who help us run the website (hosting, email delivery, payment processors, and Google for analytics or advertising when you consent). Those providers may process data on our behalf. We may also disclose information if required by law or to protect the church, our members, or the public.</p>
<h2>Retention and security</h2>
<p>We keep personal information only as long as needed for the purposes above, legal obligations, or legitimate ministry records, then delete or anonymise it. We use reasonable technical and organisational measures to protect information, but no website can be guaranteed completely secure.</p>
<h2>Newsletter location</h2>
<p>When you subscribe to our newsletter we automatically estimate your country, state/region, and city from your IP address. You do not need to type a location. This is an estimate based on where your internet provider registers the address, and it may not match where you physically are (for example, a subscriber in Imo State may appear as Rivers State if that is how the ISP maps the address). We label these records as IP estimates in our church admin tools.</p>
<h2>Google Analytics</h2>
<p>When you consent to analytics cookies, we load Google Analytics 4 (GA4) to measure page views and selected events such as newsletter sign-ups, article views, site search, and contact form submissions. We configure GA4 not to receive email addresses from our forms. You can withdraw analytics consent at any time via Cookie settings in the footer.</p>
<h2>Your rights</h2>
<p>You can update cookie preferences at any time via Cookie settings in the website footer. You may unsubscribe from newsletters using the link in those emails or by contacting us. You may ask us to access, correct, or delete personal information we hold about you, subject to any legal or pastoral record-keeping duties.</p>
<h2>Changes</h2>
<p>We may update this policy from time to time. The updated version will be posted on this page. Continued use of the site after changes means you accept the revised policy. See also our <a href="{$terms}">Terms of Use</a>.</p>
HTML;
    }

    private function defaultTermsBody(string $name, string $email): string
    {
        $privacy = e(url('/privacy'));
        $contact = e(url('/contact'));

        return <<<HTML
<p>These terms govern your use of the {$name} website. By accessing the site you agree to them. If you do not agree, please do not use the site.</p>
<h2>Ministry purpose</h2>
<p>This website shares worship information, teaching, news, and ways to connect with our church. Content is provided for ministry and educational purposes and is not a substitute for pastoral counsel or in-person fellowship.</p>
<h2>Acceptable use</h2>
<p>You may browse public pages and use forms in good faith. You must not misuse the site, attempt unauthorised access, submit unlawful or harmful content, interfere with other visitors, or use automated tools to scrape or overload the site.</p>
<h2>User submissions</h2>
<p>If you send a testimony, prayer request, registration, or other message, you confirm that you have the right to share it and that it is truthful to the best of your knowledge. We may review, edit for length or pastoral care, decline, or remove submissions. Do not include sensitive information about other people without their permission.</p>
<h2>Donations</h2>
<p>Online giving is voluntary. Payment processors handle card details; we do not store full card numbers on this website. Donation receipts and records are used for stewardship and, where applicable, church accounting. Gifts are generally non-refundable except where required by law or at the church's discretion.</p>
<h2>Advertising</h2>
<p>Some public pages may display advertisements served by Google AdSense after you consent to advertising cookies. Ads are not an endorsement of the advertised products or services. Please do not click ads except as a genuine interest in the offer; invalid or incentivised clicks violate Google's policies and these terms.</p>
<h2>Intellectual property</h2>
<p>Sermons, articles, images, logos, and other site materials belong to {$name} or their respective owners. You may share links to public pages. You may not copy substantial content for commercial use without permission.</p>
<h2>Privacy</h2>
<p>How we handle personal information is described in our <a href="{$privacy}">Privacy Policy</a>.</p>
<h2>Liability</h2>
<p>The site is provided as-is. To the fullest extent permitted by law, {$name} is not liable for loss arising from your use of the site, third-party ads or links, or interruption of service.</p>
<h2>Changes and contact</h2>
<p>We may update these terms by posting a new version on this page. Questions: <a href="mailto:{$email}">{$email}</a> or our <a href="{$contact}">contact page</a>.</p>
HTML;
    }

    private function defaultCookiePolicyBody(string $name): string
    {
        $privacy = e(url('/privacy'));

        return <<<HTML
<p>{$name} uses cookies and similar technologies so this website can function, remember your choices, and — only if you agree — measure usage and show advertising.</p>
<h2>What cookies are</h2>
<p>Cookies are small text files stored on your device. Some are strictly necessary. Others are optional and are set only after you accept them in our cookie banner.</p>
<h2>Cookie categories</h2>
<p><strong>Essential.</strong> Required for security, forms, sessions, and remembering your cookie choice. These always run.</p>
<p><strong>Analytics.</strong> Help us understand which pages are visited so we can improve the ministry website. When enabled, Google Analytics 4 (GA4) may set cookies. Analytics cookies do not load advertising and never receive your email address from our forms.</p>
<p><strong>Performance.</strong> Help pages load reliably (for example remembering preferences that improve browsing).</p>
<p><strong>Advertising (personalization).</strong> If you allow this category, Google AdSense and its partners may set cookies (including advertising cookies such as those used by Google) to serve ads based on your prior visits to this site and other sites. Third-party vendors, including Google, use cookies to serve ads based on a user's prior visits to your website or other websites. Google's use of advertising cookies enables it and its partners to serve ads to your users based on their visit to your sites and/or other sites on the Internet.</p>
<h2>How to control cookies</h2>
<p>Use Cookie settings in the website footer, or the cookie banner on your first visit, to accept all cookies, keep essential cookies only, or choose categories. You can change your mind at any time.</p>
<p>You may opt out of personalized advertising in <a href="https://www.google.com/settings/ads" rel="noopener noreferrer" target="_blank">Google Ads Settings</a> or at <a href="https://www.aboutads.info" rel="noopener noreferrer" target="_blank">www.aboutads.info</a>. Browser controls can also block or delete cookies; blocking essential cookies may break parts of the site.</p>
<p>Google explains how it uses data from partner sites at <a href="https://policies.google.com/technologies/partner-sites" rel="noopener noreferrer" target="_blank">policies.google.com/technologies/partner-sites</a>.</p>
<h2>Further information</h2>
<p>Our <a href="{$privacy}">Privacy Policy</a> describes personal information we collect through forms, donations, and the website, including that this site is not directed at children under 13.</p>
HTML;
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function resolveAgHero(array $settings): array
    {
        $fromDb = $this->readHeroFromDatabase();
        if ($fromDb !== null) {
            return $fromDb;
        }

        $hero = is_array($settings['ag']['hero'] ?? null) ? $settings['ag']['hero'] : [];
        $slide = $settings['ag']['homepage_content']['hero']['slides'][0] ?? null;
        if (is_array($slide)) {
            if (trim((string) ($hero['headline'] ?? '')) === '' && trim((string) ($slide['headline'] ?? '')) !== '') {
                $hero['headline'] = (string) $slide['headline'];
            }
            if (trim((string) ($hero['subheadline'] ?? '')) === '' && trim((string) ($slide['tagline'] ?? '')) !== '') {
                $hero['subheadline'] = (string) $slide['tagline'];
            }
            if (trim((string) ($hero['cta_label'] ?? '')) === '' && trim((string) ($slide['primary_label'] ?? '')) !== '') {
                $hero['cta_label'] = (string) $slide['primary_label'];
            }
            if (trim((string) ($hero['cta_url'] ?? '')) === '' && trim((string) ($slide['primary_url'] ?? '')) !== '') {
                $hero['cta_url'] = (string) $slide['primary_url'];
            }
            if (trim((string) ($hero['background_image'] ?? '')) === '' && trim((string) ($slide['image'] ?? '')) !== '') {
                $hero['background_image'] = (string) $slide['image'];
            }
        }

        if ($hero !== []) {
            $this->migrateHeroToDatabase($hero);

            return $hero;
        }

        return $this->defaultAgHero();
    }

    /** @return array<string, mixed>|null */
    public function readHeroFromDatabase(): ?array
    {
        if (! Schema::hasTable('ag_site_content')) {
            return null;
        }

        $row = DB::table('ag_site_content')
            ->where('section_key', WebsitePagesWriteService::HERO_SECTION_KEY)
            ->first();
        if ($row === null) {
            return null;
        }

        $decoded = json_decode((string) ($row->content_json ?? ''), true);
        if (! is_array($decoded)) {
            return null;
        }

        return [
            'headline' => trim((string) ($decoded['headline'] ?? '')),
            'subheadline' => trim((string) ($decoded['subheadline'] ?? '')),
            'cta_label' => trim((string) ($decoded['cta_label'] ?? '')),
            'cta_url' => trim((string) ($decoded['cta_url'] ?? '')),
            'background_image' => trim((string) ($decoded['background_image'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $hero */
    private function migrateHeroToDatabase(array $hero): void
    {
        if (! Schema::hasTable('ag_site_content')) {
            return;
        }

        if (DB::table('ag_site_content')->where('section_key', WebsitePagesWriteService::HERO_SECTION_KEY)->exists()) {
            return;
        }

        $json = json_encode([
            'headline' => trim((string) ($hero['headline'] ?? '')),
            'subheadline' => trim((string) ($hero['subheadline'] ?? '')),
            'cta_label' => trim((string) ($hero['cta_label'] ?? '')),
            'cta_url' => trim((string) ($hero['cta_url'] ?? '')),
            'background_image' => trim((string) ($hero['background_image'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        DB::table('ag_site_content')->insert([
            'section_key' => WebsitePagesWriteService::HERO_SECTION_KEY,
            'content_json' => $json,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function readSettings(): array
    {
        $path = storage_path('app/website/website-pages.json');
        if (! File::exists($path)) {
            return [];
        }

        $json = json_decode((string) File::get($path), true);

        return is_array($json) ? $json : [];
    }

    /** @return array<string, mixed> */
    private function defaultAgHero(): array
    {
        return [
            'headline' => 'Welcome to Assemblies of God Ikenebgu',
            'subheadline' => 'A place of worship, fellowship, and transformation.',
            'cta_label' => 'Plan Your Visit',
            'cta_url' => '/contact',
            'background_image' => '',
        ];
    }
}
