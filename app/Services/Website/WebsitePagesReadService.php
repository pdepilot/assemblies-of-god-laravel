<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\File;

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

        return array_replace_recursive($defaults, $stored);
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
        return match ($pageKey) {
            'cookie-policy' => '<p>'.$shortName.' uses essential cookies to keep the website working and optional cookies to improve your experience when you consent.</p><p>You can manage preferences anytime via the cookie banner on this site.</p>',
            'statement-of-faith' => '<p>'.$shortName.' affirms the core doctrines of the Assemblies of God: the Bible as God\'s Word, salvation through Jesus Christ, the baptism in the Holy Spirit, divine healing, and the blessed hope of Christ\'s return.</p>',
            'mission-vision' => '<p><strong>Mission:</strong> To proclaim the full Gospel of Jesus Christ, make disciples, and serve our community in love.</p><p><strong>Vision:</strong> A Spirit-filled church family transforming lives in Owerri and beyond.</p>',
            'editorial-policy' => '<p>Content published on this website aims to edify believers, share the Gospel accurately, and respect the dignity of every reader. We review submissions for biblical fidelity, clarity, and pastoral care before publication.</p>',
            'accessibility' => '<p>We strive to make '.$shortName.'\'s public website usable for people of all abilities. If you encounter a barrier, please contact us so we can improve.</p>',
            'disclaimer' => '<p>Information on this website is provided for ministry and educational purposes. It is not a substitute for pastoral counsel, professional advice, or in-person fellowship.</p>',
            'faq' => '<h2>What time are Sunday services?</h2><p>Join us for Sunday worship — see our homepage or Contact page for current service times.</p><h2>How can I visit?</h2><p>Everyone is welcome. Use Plan Your Visit on the Contact page and we will gladly help you find us.</p><h2>How can I give?</h2><p>You can give securely online via our Give page, or in person during services.</p>',
            'leadership' => '<p>Meet the pastoral and ministry leaders serving '.$shortName.'.</p>',
            'privacy' => '',
            'terms' => '',
            default => '',
        };
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function resolveAgHero(array $settings): array
    {
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

        return $hero !== [] ? $hero : $this->defaultAgHero();
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
