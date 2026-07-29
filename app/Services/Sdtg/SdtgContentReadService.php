<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgContentReadService
{
    /** @var list<string> */
    public const NESTED_SECTIONS = ['donate_page', 'livestream_page'];

    /** @var list<string> */
    public const DONATE_PAGE_TABS = ['hero', 'impact', 'give', 'donor_wall', 'map', 'feed', 'testimonials', 'transparency', 'trust', 'cta'];

    /** @var list<string> */
    public const LIVESTREAM_PAGE_TABS = ['hero', 'streams', 'current_session', 'upcoming_session', 'schedule', 'past_broadcasts', 'social', 'cta'];

    /**
     * Catalog of every editable content section.
     *
     * @return array<string, array{label: string, description: string, type: string, public_route: string, public_param: string|null}>
     */
    public function sectionCatalog(): array
    {
        return [
            'hero' => [
                'label' => 'Homepage Hero',
                'description' => 'Main banner headline, subtitle and call-to-action buttons on the SDTG homepage.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'stats' => [
                'label' => 'Homepage Stats',
                'description' => 'Animated statistic counters displayed on the homepage.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'about' => [
                'label' => 'Homepage About',
                'description' => 'The "Our Story" section including history, mission, vision and impact blocks.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'why_attend' => [
                'label' => 'Why Attend',
                'description' => 'Reasons-to-attend cards shown on the homepage.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'livestream_teaser' => [
                'label' => 'Livestream Teaser',
                'description' => 'Homepage teaser promoting the livestream page.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'donate' => [
                'label' => 'Homepage Donate Teaser',
                'description' => 'Homepage giving teaser with category cards.',
                'type' => 'flat',
                'public_route' => 'public.sdtg',
                'public_param' => null,
            ],
            'donate_page' => [
                'label' => 'Donate Page',
                'description' => 'Full donate page: hero, impact, giving categories, donor wall, map, feed, testimonials, transparency, trust and CTA.',
                'type' => 'nested',
                'public_route' => 'public.sdtg.page',
                'public_param' => 'donate',
            ],
            'livestream_page' => [
                'label' => 'Livestream Page',
                'description' => 'Full livestream page: hero, stream sources, sessions, schedule, past broadcasts, social and CTA.',
                'type' => 'nested',
                'public_route' => 'public.sdtg.page',
                'public_param' => 'livestream',
            ],
        ];
    }

    public function isKnownSection(string $key): bool
    {
        return array_key_exists($key, $this->sectionCatalog());
    }

    /** @return array<string, list<string>> */
    public function nestedTabs(): array
    {
        return [
            'donate_page' => self::DONATE_PAGE_TABS,
            'livestream_page' => self::LIVESTREAM_PAGE_TABS,
        ];
    }

    /** @return list<string> */
    public function tabsFor(string $key): array
    {
        return $this->nestedTabs()[$key] ?? [];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'International Music Crusade',
                'title_line1' => 'SEND DOWN',
                'title_line2' => 'THY GLORY',
                'subtitle' => "An International Music Crusade where lives are transformed through worship, prayer, miracles, and the power of God's presence.",
                'primary_label' => 'Register Now',
                'primary_link' => 'registration',
                'secondary_label' => 'Watch Previous Crusades',
                'secondary_link' => '#gallery',
                'video_url' => '',
                'image' => 'img/lifted_hands.jpeg',
            ],
            'stats' => [
                'items' => [
                    ['icon' => 'fa-cross', 'target' => 12, 'suffix' => '+', 'label' => 'Years of Ministry'],
                    ['icon' => 'fa-users', 'target' => 250000, 'suffix' => '+', 'label' => 'Total Attendees'],
                    ['icon' => 'fa-globe-africa', 'target' => 45, 'suffix' => '+', 'label' => 'Nations Represented'],
                    ['icon' => 'fa-microphone-alt', 'target' => 180, 'suffix' => '+', 'label' => 'Guest Ministers Hosted'],
                    ['icon' => 'fa-heart', 'target' => 50000, 'suffix' => '+', 'label' => 'Souls Impacted'],
                ],
            ],
            'about' => [
                'eyebrow' => 'Our Story',
                'heading' => 'About Send Down Thy Glory',
                'lead' => "Born from a burning desire to see God's glory descend upon nations, SDTG has grown from a local worship gathering into one of Africa's most anticipated international gospel crusades.",
                'image' => 'img/lifted_hands.jpeg',
                'badge_number' => '12+',
                'badge_text' => 'Years of Glory',
                'blocks' => [
                    ['icon' => 'fa-history', 'title' => 'Our History', 'text' => 'Since 2014, Send Down Thy Glory has convened believers across continents — creating sacred spaces where heaven touches earth through music, prayer, and the Word.'],
                    ['icon' => 'fa-bullseye', 'title' => 'Our Mission', 'text' => 'To ignite revival through worship, deliver life-transforming messages, and create an atmosphere where miracles, healing, and salvation become everyday experiences.'],
                    ['icon' => 'fa-eye', 'title' => 'Our Vision', 'text' => 'A generation fully awakened to the glory of God — carrying revival fire from Owerri to the ends of the earth, one crusade at a time.'],
                    ['icon' => 'fa-fire-alt', 'title' => 'Spiritual Impact', 'text' => 'Countless testimonies of healing, deliverance, career breakthroughs, and family restorations bear witness to the move of God at every SDTG edition.'],
                ],
            ],
            'why_attend' => [
                'eyebrow' => 'The SDTG Experience',
                'heading' => 'Why People Attend SDTG',
                'cards' => [
                    ['icon' => 'fa-music', 'title' => 'Powerful Worship', 'text' => 'Experience worship that shifts atmospheres and ushers in the tangible presence of God.'],
                    ['icon' => 'fa-book-open', 'title' => 'Life-Changing Messages', 'text' => 'Messages birthed in prayer that challenge, inspire, and transform every area of your life.'],
                    ['icon' => 'fa-hands-praying', 'title' => 'Healing & Miracles', 'text' => 'Witness and receive supernatural healings, deliverances, and breakthrough miracles.'],
                    ['icon' => 'fa-pray', 'title' => 'Prayer Encounters', 'text' => "Join thousands in unified prayer that moves mountains and releases heaven's power."],
                    ['icon' => 'fa-handshake', 'title' => 'Networking with Believers', 'text' => 'Connect with faith-filled believers, ministers, and leaders from around the world.'],
                    ['icon' => 'fa-seedling', 'title' => 'Spiritual Growth', 'text' => 'Leave refreshed, renewed, and equipped to carry the fire of revival into your community.'],
                ],
            ],
            'livestream_teaser' => [
                'eyebrow' => 'Experience From Anywhere',
                'heading' => 'Watch SDTG Live',
                'description' => "Can't make it in person? Join the global family streaming live from anywhere in the world. Experience every moment of worship, word, and wonder.",
                'youtube_url' => '#',
                'facebook_url' => '#',
                'cta_label' => 'Watch Live',
                'cta_link' => 'livestream',
                'preview_image' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=800&q=80',
            ],
            'donate' => [
                'eyebrow' => 'Sow Into Revival',
                'heading' => 'Support The Vision',
                'description' => 'Your giving fuels the spread of the gospel and enables us to reach more souls for Christ',
                'cta_label' => 'Support The Vision',
                'cta_link' => 'donate',
                'cards' => [
                    ['icon' => 'fa-church', 'title' => 'Tithes', 'text' => "Honor God with the firstfruits of your increase and partner with heaven's agenda."],
                    ['icon' => 'fa-gift', 'title' => 'Offering', 'text' => 'Give willingly and watch God multiply your seed beyond measure.'],
                    ['icon' => 'fa-heart', 'title' => 'Charity', 'text' => 'Support outreach programs bringing hope to the less privileged.'],
                    ['icon' => 'fa-hammer', 'title' => 'Project Support', 'text' => 'Fund crusade infrastructure, equipment, and global expansion initiatives.'],
                ],
            ],
            'donate_page' => $this->defaultDonatePageContent(),
            'livestream_page' => $this->defaultLivestreamPageContent(),
        ];
    }

    /** @return array<string, mixed> */
    public function defaultSection(string $key): array
    {
        return $this->defaults()[$key] ?? [];
    }

    /**
     * Section content merged with defaults so every field is always present.
     *
     * @return array<string, mixed>
     */
    public function getSection(string $key): array
    {
        $default = $this->defaultSection($key);
        $stored = $this->getRawStored($key);

        if ($stored === null) {
            return $default;
        }

        return in_array($key, self::NESTED_SECTIONS, true)
            ? $this->mergeNested($default, $stored)
            : array_replace($default, $stored);
    }

    /**
     * Deep-merge stored content over defaults, but treat any list (sequential array —
     * e.g. repeater rows like categories/cards/schedule) as an atomic value that is
     * replaced wholesale rather than merged index-by-index. Plain array_replace_recursive
     * would otherwise splice stale default rows back into a shortened stored list.
     *
     * @param array<string, mixed> $default
     * @param array<string, mixed> $stored
     * @return array<string, mixed>
     */
    private function mergeNested(array $default, array $stored): array
    {
        $merged = $default;
        foreach ($stored as $key => $value) {
            if (is_array($value) && ! array_is_list($value) && is_array($merged[$key] ?? null) && ! array_is_list($merged[$key])) {
                $merged[$key] = $this->mergeNested($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /** @return array<string, mixed>|null */
    public function getRawStored(string $key): ?array
    {
        $raw = DB::table('sdtg_site_content')->where('section_key', $key)->value('content_json');
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Always returns all catalog sections, each annotated with DB metadata when present.
     *
     * @return list<array<string, mixed>>
     */
    public function listCatalogSections(): array
    {
        $rows = DB::table('sdtg_site_content')
            ->whereIn('section_key', array_keys($this->sectionCatalog()))
            ->get(['section_key', 'updated_at'])
            ->keyBy('section_key');

        $sections = [];
        foreach ($this->sectionCatalog() as $key => $meta) {
            $row = $rows->get($key);
            $sections[] = array_merge($meta, [
                'key' => $key,
                'updated_at' => $row->updated_at ?? null,
            ]);
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    private function defaultDonatePageContent(): array
    {
        return [
            'hero' => [
                'badge' => 'Sow Into Revival',
                'title_before' => 'Support',
                'title_highlight' => 'The Vision',
                'subtitle' => 'Every gift helps spread hope, transform lives, and advance the work of God\'s Kingdom.',
                'primary_label' => 'Give Now',
                'primary_link' => '#give',
                'secondary_label' => 'See Impact Stories',
                'secondary_link' => '#impact-stories',
                'image' => 'img/lifted_hands.jpeg',
            ],
            'impact' => [
                'eyebrow' => 'Your Impact',
                'heading' => 'What Your Giving Supports',
                'cards' => [
                    ['icon' => 'fa-cross', 'title' => 'Crusade Support', 'text' => 'Stage, sound, logistics, and infrastructure for world-class crusades.', 'progress' => 85],
                    ['icon' => 'fa-bullhorn', 'title' => 'Evangelism', 'text' => 'Outreach teams, tracts, and soul-winning initiatives across communities.', 'progress' => 72],
                    ['icon' => 'fa-hand-holding-heart', 'title' => 'Charity Projects', 'text' => 'Food drives, medical outreach, and support for the less privileged.', 'progress' => 68],
                    ['icon' => 'fa-video', 'title' => 'Media Ministry', 'text' => 'Broadcasting, livestreams, and global digital gospel reach.', 'progress' => 90],
                    ['icon' => 'fa-child', 'title' => 'Youth Development', 'text' => 'SDTG Youth Movement, mentorship, and leadership training.', 'progress' => 55],
                    ['icon' => 'fa-music', 'title' => 'Worship Ministry', 'text' => 'Choir academy, musicians, and worship resource development.', 'progress' => 78],
                    ['icon' => 'fa-users', 'title' => 'Community Outreach', 'text' => 'Local missions, prison ministry, and community transformation programs.', 'progress' => 62],
                ],
            ],
            'give' => [
                'eyebrow' => 'Give Securely',
                'heading' => 'Choose Your Giving Category',
                'description' => 'Select a category to view Nigerian and international bank details.',
                'categories' => [
                    [
                        'key' => 'tithes', 'icon' => 'fa-church', 'title' => 'Tithes',
                        'nigeria' => ['bank' => 'Zenith Bank', 'account_name' => 'Send Down Thy Glory Ministry', 'account_number' => '1234567890'],
                        'international' => ['bank' => 'Zenith Bank Nigeria', 'account_name' => 'Send Down Thy Glory Ministry', 'account_number' => '1234567890', 'swift' => 'ZENINNG', 'iban' => 'NG1234567890123456789012'],
                    ],
                    [
                        'key' => 'offering', 'icon' => 'fa-gift', 'title' => 'Offering',
                        'nigeria' => ['bank' => 'GTBank', 'account_name' => 'SDTG Offering Account', 'account_number' => '0987654321'],
                        'international' => ['bank' => 'GTBank Nigeria', 'account_name' => 'SDTG Offering Account', 'account_number' => '0987654321', 'swift' => 'GTBINGLA', 'iban' => 'NG9876543210987654321098'],
                    ],
                    [
                        'key' => 'charity', 'icon' => 'fa-heart', 'title' => 'Charity',
                        'nigeria' => ['bank' => 'Access Bank', 'account_name' => 'SDTG Charity Outreach', 'account_number' => '1122334455'],
                        'international' => ['bank' => 'Access Bank Nigeria', 'account_name' => 'SDTG Charity Outreach', 'account_number' => '1122334455', 'swift' => 'ABNGNGLA', 'iban' => 'NG1122334455112233445566'],
                    ],
                    [
                        'key' => 'missions', 'icon' => 'fa-globe-africa', 'title' => 'Missions',
                        'nigeria' => ['bank' => 'First Bank', 'account_name' => 'SDTG Missions Fund', 'account_number' => '5566778899'],
                        'international' => ['bank' => 'First Bank Nigeria', 'account_name' => 'SDTG Missions Fund', 'account_number' => '5566778899', 'swift' => 'FBNINGLA', 'iban' => 'NG5566778899556677889900'],
                    ],
                    [
                        'key' => 'project', 'icon' => 'fa-hammer', 'title' => 'Project Support',
                        'nigeria' => ['bank' => 'UBA', 'account_name' => 'SDTG Project Support', 'account_number' => '6677889900'],
                        'international' => ['bank' => 'UBA Nigeria', 'account_name' => 'SDTG Project Support', 'account_number' => '6677889900', 'swift' => 'UNAFNGLA', 'iban' => 'NG6677889900667788990011'],
                    ],
                    [
                        'key' => 'partnership', 'icon' => 'fa-handshake', 'title' => 'Partnership Giving',
                        'nigeria' => ['bank' => 'Stanbic IBTC', 'account_name' => 'SDTG Partnership Giving', 'account_number' => '3344556677'],
                        'international' => ['bank' => 'Stanbic IBTC Nigeria', 'account_name' => 'SDTG Partnership Giving', 'account_number' => '3344556677', 'swift' => 'SBICNGLX', 'iban' => 'NG3344556677334455667788'],
                    ],
                ],
            ],
            'donor_wall' => [
                'eyebrow' => 'Transparency',
                'heading' => 'Wall Of Impact',
                'description' => 'Recent partners who are advancing the gospel through generous giving.',
                'donors' => [
                    ['name' => 'Emmanuel Adebayo', 'meta' => 'Lagos, Nigeria · Aug 2025', 'amount' => '₦500,000', 'purpose' => 'Crusade Support', 'phone' => '0803****456'],
                    ['name' => 'Sarah Mitchell', 'meta' => 'London, UK · Jul 2025', 'amount' => '£2,500', 'purpose' => 'Media Equipment', 'phone' => '+447****892'],
                    ['name' => 'Anonymous Partner', 'meta' => 'Canada · Jul 2025', 'amount' => '$5,000', 'purpose' => 'Media Ministry', 'phone' => ''],
                    ['name' => 'Pastor Daniel Okwu', 'meta' => 'Enugu, Nigeria · Jun 2025', 'amount' => '₦250,000', 'purpose' => 'Charity Outreach', 'phone' => '0813****901'],
                    ['name' => 'Grace Fellowship Church', 'meta' => 'Accra, Ghana · Jun 2025', 'amount' => '₵15,000', 'purpose' => 'Worship Ministry', 'phone' => ''],
                    ['name' => 'Chioma Nwankwo', 'meta' => 'Abuja, Nigeria · May 2025', 'amount' => '₦100,000', 'purpose' => 'Youth Programs', 'phone' => '0705****234'],
                ],
            ],
            'map' => [
                'eyebrow' => 'Global Family',
                'heading' => 'Supporters Around The World',
                'stats' => [
                    ['target' => 45, 'suffix' => '+', 'label' => 'Countries'],
                    ['target' => 3200, 'suffix' => '+', 'label' => 'Supporters'],
                    ['target' => 180, 'suffix' => '+', 'label' => 'Partner Churches'],
                ],
            ],
            'feed' => [
                'items' => [
                    'Anonymous donor from Canada supported Media Ministry.',
                    'Partner from Nigeria donated to Crusade Support.',
                    'Supporter from the United Kingdom donated to Charity Outreach.',
                    'Grace Fellowship Church (Ghana) gave to Worship Ministry.',
                ],
            ],
            'testimonials' => [
                'eyebrow' => 'Lives Changed',
                'heading' => 'Testimonies Of Impact',
                'items' => [
                    ['image' => 'img/main1.jpg', 'quote' => '"Your charity giving fed 500 families in rural Imo State. Children who hadn\'t eaten in days received hope and the gospel."', 'author' => 'Outreach Team Lead', 'location' => 'Owerri, Nigeria'],
                    ['image' => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80', 'quote' => '"Partnership giving equipped our choir academy. 120 youth now lead worship in their communities across 8 states."', 'author' => 'SDTG Choir Director', 'location' => 'Nigeria'],
                    ['image' => 'https://images.unsplash.com/photo-1507692049790-bcf87f88310a?w=400&q=80', 'quote' => '"Crusade support made it possible to reach 30,000 souls. Over 5,000 gave their lives to Christ in a single night."', 'author' => 'Rev. Ministry Lead', 'location' => 'SDTG 2025'],
                ],
            ],
            'transparency' => [
                'eyebrow' => 'Accountability',
                'heading' => 'Transparency At A Glance',
                'stats' => [
                    ['target' => 850, 'suffix' => 'M+', 'label' => 'Funds Raised (₦)'],
                    ['target' => 48, 'suffix' => '+', 'label' => 'Projects Completed'],
                    ['target' => 120, 'suffix' => '+', 'label' => 'Communities Reached'],
                    ['target' => 35, 'suffix' => '+', 'label' => 'Outreach Programs'],
                ],
            ],
            'trust' => [
                'items' => [
                    ['icon' => 'fa-shield-alt', 'title' => 'Secure Giving', 'text' => 'Bank-grade security for all transactions'],
                    ['icon' => 'fa-file-invoice-dollar', 'title' => 'Transparent Accounting', 'text' => 'Annual reports published for partners'],
                    ['icon' => 'fa-globe-americas', 'title' => 'International Donations', 'text' => 'SWIFT & IBAN transfers accepted worldwide'],
                    ['icon' => 'fa-award', 'title' => 'Trusted Ministry', 'text' => '12+ years of faithful stewardship'],
                ],
            ],
            'cta' => [
                'title' => 'Become Part Of The Story',
                'subtitle' => 'Your generosity helps create moments of worship, transformation, outreach, and hope for generations to come.',
                'primary_label' => 'Donate Now',
                'primary_link' => '#give',
                'secondary_label' => 'Become A Partner',
                'secondary_link' => 'contact',
                'image' => 'img/lifted_hands.jpeg',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function defaultLivestreamPageContent(): array
    {
        return [
            'hero' => [
                'badge' => 'Global Broadcast · Live From Owerri',
                'title_before' => 'Watch Send Down Thy Glory',
                'title_highlight' => 'Live',
                'subtitle' => 'Join thousands of worshippers around the world for a powerful encounter of worship, prayer, miracles, and transformation.',
                'primary_label' => 'Watch Live Now',
                'primary_link' => '#player',
                'secondary_label' => 'Register For Crusade',
                'secondary_link' => 'registration',
                'tertiary_label' => 'Submit Prayer Request',
                'tertiary_link' => '#prayer',
                'video' => 'videos/worship-hero.mp4',
                'poster' => 'img/lifted_hands.jpeg',
            ],
            'streams' => [
                'youtube' => ['id' => 'LXb3EKWsInQ', 'label' => 'YouTube Live'],
                'facebook' => ['id' => '', 'label' => 'Facebook Live', 'embed' => ''],
                'vimeo' => ['id' => '76979871', 'label' => 'Vimeo'],
                'custom' => ['url' => 'videos/worship-crowd.mp4', 'label' => 'SDTG Direct'],
                'default_platform' => 'youtube',
            ],
            'current_session' => [
                'title' => 'Opening Worship & Praise',
                'minister' => 'To Be Announced',
                'ministry' => '',
                'country' => '',
                'worship_team' => 'SDTG Mass Choir',
                'photo' => 'img/lifted_hands.jpeg',
                'topic' => '',
            ],
            'upcoming_session' => [
                'title' => 'Keynote Session',
                'minister' => 'To Be Announced',
                'time' => 'TBA',
            ],
            'schedule' => [
                ['time' => '6:00 PM', 'session' => 'Pre-Service Worship', 'speaker' => 'SDTG Worship Team', 'worship' => 'Mass Choir'],
                ['time' => '7:00 PM', 'session' => 'Opening Address', 'speaker' => 'To Be Announced', 'worship' => '—'],
                ['time' => '7:30 PM', 'session' => 'Worship Encounter', 'speaker' => 'To Be Announced', 'worship' => 'To Be Announced'],
                ['time' => '8:00 PM', 'session' => 'Keynote Message', 'speaker' => 'To Be Announced', 'worship' => '—'],
                ['time' => '9:30 PM', 'session' => 'Altar Call & Prayer', 'speaker' => 'Revival Team', 'worship' => 'To Be Announced'],
                ['time' => '10:30 PM', 'session' => 'Closing Worship', 'speaker' => '—', 'worship' => 'To Be Announced'],
            ],
            'past_broadcasts' => [
                ['title' => 'SDTG 2025 — Glory Without Limits', 'year' => '2025', 'duration' => '3h 42m', 'thumb' => 'img/lifted_hands.jpeg', 'video' => 'videos/worship-hero.mp4', 'type' => 'local'],
                ['title' => "SDTG 2024 — Heaven's Sound", 'year' => '2024', 'duration' => '3h 18m', 'thumb' => 'img/main1.jpg', 'video' => 'LXb3EKWsInQ', 'type' => 'youtube'],
                ['title' => 'SDTG 2023 — Revival Fire', 'year' => '2023', 'duration' => '2h 55m', 'thumb' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=640&q=80', 'video' => 'LXb3EKWsInQ', 'type' => 'youtube'],
                ['title' => 'SDTG 2022 — Return Of Glory', 'year' => '2022', 'duration' => '2h 40m', 'thumb' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&q=80', 'video' => 'videos/worship-crowd.mp4', 'type' => 'local'],
            ],
            'social' => [
                ['platform' => 'facebook', 'icon' => 'fab fa-facebook-f', 'label' => 'Facebook', 'subtitle' => 'Live Reactions', 'url' => '#'],
                ['platform' => 'instagram', 'icon' => 'fab fa-instagram', 'label' => 'Instagram', 'subtitle' => 'Stories & Clips', 'url' => '#'],
                ['platform' => 'youtube', 'icon' => 'fab fa-youtube', 'label' => 'YouTube', 'subtitle' => 'Live Stream', 'url' => '#'],
                ['platform' => 'tiktok', 'icon' => 'fab fa-tiktok', 'label' => 'TikTok', 'subtitle' => 'Highlights', 'url' => '#'],
                ['platform' => 'x', 'icon' => 'fab fa-x-twitter', 'label' => 'X', 'subtitle' => 'Live Updates', 'url' => '#'],
            ],
            'cta' => [
                'title' => 'Share The Experience',
                'subtitle' => 'Invite friends and family to encounter the glory of God with you.',
                'primary_label' => 'Invite Friends',
                'secondary_label' => 'Register Now',
                'secondary_link' => 'registration',
                'tertiary_label' => 'Support The Vision',
                'tertiary_link' => 'donate',
                'image' => 'img/main1.jpg',
            ],
        ];
    }
}
