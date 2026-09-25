<?php

namespace App\Services\PublicSite;

use App\Services\Website\ActivityReadService;
use App\Services\Website\WebsitePagesReadService;
use App\Services\Website\WorshipReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class PublicHomepageReadService
{
    public function __construct(
        private readonly PublicAssetResolver $assets,
        private readonly WorshipReadService $worship,
        private readonly ActivityReadService $activities,
    ) {}

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $settings = $this->readWebsitePages();
        $homepage = $this->homepageContentFromSettings($settings);
        $hero = is_array($homepage['hero'] ?? null) ? $homepage['hero'] : [];
        $slides = is_array($hero['slides'] ?? null) ? $hero['slides'] : [];
        if ($slides === []) {
            $slides = $this->defaultHomepageContent()['hero']['slides'];
        }

        $slides = $this->applySimpleHeroOverlay($slides, $settings);

        $slides = array_map(function (array $slide): array {
            if (! empty($slide['image'])) {
                $slide['image'] = $this->siteAsset((string) $slide['image']);
            }

            return $slide;
        }, $slides);

        return [
            'church' => $this->church(),
            'preloader' => is_array($homepage['preloader'] ?? null)
                ? $homepage['preloader']
                : $this->defaultHomepageContent()['preloader'],
            'hero_interval_ms' => max(2000, min(30000, (int) ($hero['interval_ms'] ?? 7000))),
            'hero_slides' => $slides,
            'about' => $this->aboutSection(),
            'activities' => $this->activities->published(),
            'events' => $this->events(),
            'sermons' => $this->sermons(),
            'team' => $this->team(),
            'worshipPrograms' => $this->worship->programs(),
            'worshipLocation' => $this->worship->location(),
            'legacy_base' => url('/'),
            // Browser JS posts to Laravel /api (proxied). Do not expose PORTAL_LEGACY_API_BASE.
            'legacy_api_base' => rtrim(url('/api'), '/'),
            'asset_base' => asset('site'),
            'traffic_beacon_url' => url('/api/track-traffic'),
        ];
    }

    public function legacyUrl(string $path = ''): string
    {
        $path = ltrim(trim($path), '/');
        $map = [
            '' => '/',
            'about' => '/about',
            'activity' => '/activity',
            'event' => '/event',
            'blog' => '/blog',
            'contact' => '/contact',
            'donate' => '/donate',
            'privacy' => '/privacy',
            'terms' => '/terms',
            'sermon-library/' => '/sermons',
            'sermon-library/index' => '/sermons',
        ];

        if (isset($map[$path])) {
            return url($map[$path]);
        }

        return url('/'.$path);
    }

    public function siteAsset(string $path): string
    {
        return $this->assets->url($path);
    }

    /** @return array<string, mixed> */
    private function homepageContent(): array
    {
        return $this->homepageContentFromSettings($this->readWebsitePages());
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function homepageContentFromSettings(array $settings): array
    {
        $defaults = $this->defaultHomepageContent();
        $stored = is_array($settings['ag']['homepage_content'] ?? null)
            ? $settings['ag']['homepage_content']
            : [];

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * Map admin "Pages → Hero" fields onto the first public slide.
     *
     * @param  list<array<string, mixed>>  $slides
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>
     */
    private function applySimpleHeroOverlay(array $slides, array $settings): array
    {
        $hero = app(WebsitePagesReadService::class)->readHeroFromDatabase();
        if ($hero === null) {
            $hero = is_array($settings['ag']['hero'] ?? null) ? $settings['ag']['hero'] : [];
        }
        if ($hero === [] || $slides === []) {
            return $slides;
        }

        $first = is_array($slides[0] ?? null) ? $slides[0] : [];
        if (trim((string) ($hero['headline'] ?? '')) !== '') {
            $first['headline'] = (string) $hero['headline'];
        }
        if (trim((string) ($hero['subheadline'] ?? '')) !== '') {
            $first['tagline'] = (string) $hero['subheadline'];
        }
        if (trim((string) ($hero['cta_label'] ?? '')) !== '') {
            $first['primary_label'] = (string) $hero['cta_label'];
        }
        if (trim((string) ($hero['cta_url'] ?? '')) !== '') {
            $cta = (string) $hero['cta_url'];
            $first['primary_url'] = str_starts_with($cta, 'http') ? $cta : ltrim($cta, '/');
        }
        if (trim((string) ($hero['background_image'] ?? '')) !== '') {
            $first['image'] = (string) $hero['background_image'];
        }
        $slides[0] = $first;

        return $slides;
    }

    /** @return array<string, mixed> */
    private function readWebsitePages(): array
    {
        $candidates = array_filter([
            storage_path('app/website/website-pages.json'),
            (string) config('portal.website_pages_path'),
        ]);

        foreach ($candidates as $path) {
            if ($path === '' || ! File::isFile($path)) {
                continue;
            }
            $decoded = json_decode((string) File::get($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function defaultHomepageContent(): array
    {
        return [
            'preloader' => [
                'enabled' => true,
                'video_url' => 'videos/Create_a_cinematic_D_animatio.mp4',
                'show_skip' => false,
                'max_wait_ms' => 120000,
            ],
            'hero' => [
                'interval_ms' => 7000,
                'slides' => [
                    [
                        'image' => 'images/main1.jpg',
                        'tagline' => 'Assemblies of God · Ikenebgu',
                        'headline' => 'Welcome Home — A Place to Grow in Faith and Fellowship',
                        'primary_label' => 'About Our Church',
                        'primary_url' => 'about',
                        'secondary_label' => 'Plan Your Visit',
                        'secondary_url' => 'contact',
                    ],
                    [
                        'image' => 'images/church2.webp',
                        'tagline' => 'Sunday Worship · Prayer · Praise',
                        'headline' => "Experience God's Love, Grace, and Presence",
                        'primary_label' => 'Watch Sermons',
                        'primary_url' => 'sermon-library/index',
                        'secondary_label' => 'Upcoming Events',
                        'secondary_url' => 'event',
                    ],
                    [
                        'image' => 'images/main2.jpg',
                        'tagline' => "Families · Youth · Children's Ministry",
                        'headline' => 'Building Strong Families Through Christ',
                        'primary_label' => 'Explore Ministries',
                        'primary_url' => 'activity',
                        'secondary_label' => 'Meet Our Leaders',
                        'secondary_url' => 'team',
                    ],
                    [
                        'image' => 'images/rev1.jpg',
                        'tagline' => 'Outreach · Compassion · Hope',
                        'headline' => 'Serving Our Community with Compassion and Hope',
                        'primary_label' => 'Community Programs',
                        'primary_url' => 'activity',
                        'secondary_label' => 'Give',
                        'secondary_url' => 'donate',
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function church(): array
    {
        $publicIdentity = config('identity.public');
        $defaults = [
            'church_name' => (string) ($publicIdentity['site_name'] ?? 'AGC Ikenegbu Assemblies of God'),
            'short_name' => (string) ($publicIdentity['short_name'] ?? 'AGC Ikenegbu'),
            'phone' => '+2348034095171',
            'phone_display' => '08034095171',
            'phone_tel' => '+2348034095171',
            'email' => 'info@agikenebgu.org',
            'address_full' => 'AGC Ikenegbu, Ikenebgu Layout, Owerri, Imo State',
            'sunday_worship' => '8:00 AM & 10:30 AM',
            'midweek_service' => 'Wednesday Bible Study — 6:00 PM',
            'prayer_meeting' => 'Friday Prayer — 6:00 PM',
            'social' => [
                'facebook' => '#',
                'instagram' => '#',
                'youtube' => '#',
                'whatsapp' => '#',
                'twitter' => '#',
                'linkedin' => '#',
            ],
        ];

        if (! Schema::hasTable('contact_settings')) {
            return $defaults;
        }

        $row = DB::table('contact_settings')->orderBy('id')->first();
        if (! $row) {
            return $defaults;
        }

        $data = (array) $row;
        $phoneDisplay = trim((string) ($data['phone_display'] ?? ''));
        if ($phoneDisplay === '') {
            $phoneDisplay = trim((string) ($data['phone'] ?? ''));
        }
        $phoneTel = preg_replace('/[^\d+]/', '', (string) ($data['phone'] ?? $phoneDisplay)) ?: $phoneDisplay;

        $addressParts = array_filter([
            $data['address_line1'] ?? '',
            $data['address_line2'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['country'] ?? '',
        ], static fn ($part) => trim((string) $part) !== '');

        $name = trim((string) ($data['church_name'] ?? ''));

        return [
            'church_name' => $name !== '' ? $name : $defaults['church_name'],
            'short_name' => $defaults['short_name'],
            'phone' => $phoneTel !== '' ? $phoneTel : $defaults['phone'],
            'phone_display' => $phoneDisplay !== '' ? $phoneDisplay : $defaults['phone_display'],
            'phone_tel' => $phoneTel !== '' ? $phoneTel : $defaults['phone_tel'],
            'email' => trim((string) ($data['email'] ?? '')) ?: $defaults['email'],
            'address_full' => $addressParts !== [] ? implode(', ', $addressParts) : $defaults['address_full'],
            'sunday_worship' => trim((string) ($data['sunday_worship'] ?? '')) ?: $defaults['sunday_worship'],
            'midweek_service' => trim((string) ($data['midweek_service'] ?? '')) ?: $defaults['midweek_service'],
            'prayer_meeting' => trim((string) ($data['prayer_meeting'] ?? '')) ?: $defaults['prayer_meeting'],
            'social' => [
                'facebook' => trim((string) ($data['facebook_url'] ?? '')) ?: '#',
                'instagram' => trim((string) ($data['instagram_url'] ?? '')) ?: '#',
                'youtube' => trim((string) ($data['youtube_url'] ?? '')) ?: '#',
                'whatsapp' => trim((string) ($data['whatsapp_url'] ?? '')) ?: '#',
                'twitter' => trim((string) ($data['twitter_url'] ?? '')) ?: '#',
                'linkedin' => trim((string) ($data['linkedin_url'] ?? '')) ?: '#',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function aboutSection(): array
    {
        $publicIdentity = config('identity.public');
        $defaults = [
            'eyebrow' => 'About Us',
            'title' => 'A Church Family Rooted in Faith, Love, and Service',
            'intro' => sprintf(
                '%s is a spirit-filled Assemblies of God church in Owerri, welcoming every heart to worship, grow, and serve.',
                (string) ($publicIdentity['short_name'] ?? 'AGC Ikenegbu')
            ),
            'vision_title' => 'Our Vision',
            'vision_text' => 'To raise disciples who know Christ and make Him known.',
            'mission_title' => 'Our Mission',
            'mission_text' => 'Transforming the lives of people to be heavenly conscious and earthly useful',
            'gallery' => [
                ['image' => 'images/main1.jpg', 'alt' => ((string) ($publicIdentity['short_name'] ?? 'AGC Ikenegbu')).' church'],
                ['image' => 'images/main2.jpg', 'alt' => 'Worship gathering'],
                ['image' => 'images/church2.webp', 'alt' => 'Church community'],
            ],
            'highlight' => [],
            'features' => [],
            'scripture_banner' => [
                'cta_label' => 'Learn More',
                'cta_url' => 'about',
                'verses' => [
                    ['quote' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.', 'reference' => 'John 3:16 (KJV)'],
                    ['quote' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.', 'reference' => 'Proverbs 3:5–6 (KJV)'],
                    ['quote' => 'I can do all things through Christ which strengtheneth me.', 'reference' => 'Philippians 4:13 (KJV)'],
                    ['quote' => 'The LORD bless thee, and keep thee: the LORD make his face shine upon thee, and be gracious unto thee: the LORD lift up his countenance upon thee, and give thee peace.', 'reference' => 'Numbers 6:24–26 (KJV)'],
                    ['quote' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.', 'reference' => 'Matthew 11:28 (KJV)'],
                ],
            ],
        ];

        if (! Schema::hasTable('ag_site_content')) {
            return $this->resolveAboutMedia($defaults);
        }

        $row = DB::table('ag_site_content')->where('section_key', 'homepage_about')->first();
        if (! $row) {
            return $this->resolveAboutMedia($defaults);
        }

        $content = json_decode((string) ($row->content_json ?? '{}'), true);

        return $this->resolveAboutMedia(array_replace_recursive($defaults, is_array($content) ? $content : []));
    }

    /** @param array<string, mixed> $content @return array<string, mixed> */
    private function resolveAboutMedia(array $content): array
    {
        if (is_array($content['gallery'] ?? null)) {
            $content['gallery'] = array_map(function ($item) {
                if (is_array($item) && ! empty($item['image'])) {
                    $item['image'] = $this->siteAsset((string) $item['image']);
                }

                return $item;
            }, $content['gallery']);
        }

        if (is_array($content['highlight'] ?? null) && ! empty($content['highlight']['image'])) {
            $content['highlight']['image'] = $this->siteAsset((string) $content['highlight']['image']);
        }

        return $content;
    }

    /** @return list<array<string, mixed>> */
    private function events(): array
    {
        if (! Schema::hasTable('church_events')) {
            return [];
        }

        return DB::table('church_events')
            ->where('is_published', 1)
            ->where('status', 'upcoming')
            ->orderBy('sort_order')
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->map(function ($row): array {
                $event = (array) $row;
                $imagePath = (string) ($event['image_path'] ?? 'img/events-1.jpg');
                $event['image_url'] = $this->siteAsset($imagePath !== '' ? $imagePath : 'img/events-1.jpg');
                $event['public_category_label'] = ucfirst((string) ($event['category'] ?? 'Event'));
                $event['recurrence_label'] = (string) ($event['recurrence_label'] ?? '');
                $event['schedule_display'] = (string) ($event['schedule_display'] ?? '');
                $event['cta_url'] = trim((string) ($event['cta_url'] ?? ''));
                $event['cta_text'] = trim((string) ($event['cta_text'] ?? 'Learn more')) ?: 'Learn more';

                return $event;
            })
            ->all();
    }

    /** @return array{eyebrow: string, title: string, cards: list<array<string, mixed>>} */
    private function sermons(): array
    {
        $section = [
            'eyebrow' => 'Sermons',
            'title' => 'Messages That Strengthen Your Walk With God',
            'cards' => [],
        ];

        if (! Schema::hasTable('sermons')) {
            $section['cards'] = $this->fallbackSermonCards();

            return $section;
        }

        $query = DB::table('sermons as s')
            ->where('s.status', 'published');

        $pinned = (clone $query)
            ->where('s.show_on_homepage', 1)
            ->orderBy('s.homepage_order')
            ->orderByDesc('s.published_at')
            ->limit(3)
            ->get();

        $rows = $pinned->isNotEmpty()
            ? $pinned
            : $query->orderByDesc('s.published_at')->orderByDesc('s.sermon_date')->limit(3)->get();

        if ($rows->isEmpty()) {
            $section['cards'] = $this->fallbackSermonCards();

            return $section;
        }

        $section['cards'] = $rows->map(function ($row): array {
            $sermon = (array) $row;
            $image = trim((string) ($sermon['featured_image'] ?? ''));
            if ($image === '') {
                $image = 'img/sermon-'.((((int) ($sermon['id'] ?? 1)) % 3) + 1).'.jpg';
            }
            $dateLabel = '';
            if (! empty($sermon['sermon_date'])) {
                $ts = strtotime((string) $sermon['sermon_date']);
                $dateLabel = $ts ? date('d M Y', $ts) : (string) $sermon['sermon_date'];
            }

            $slug = trim((string) ($sermon['slug'] ?? ''));

            return [
                'title' => (string) ($sermon['title'] ?? 'Sermon'),
                'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($sermon['description'] ?? $sermon['content_html'] ?? '')), 220),
                'image_url' => $this->siteAsset($image),
                'date_label' => $dateLabel,
                'author' => (string) ($sermon['minister_name'] ?? 'Pastor'),
                'link' => $slug !== '' ? route('public.sermons.show', $slug) : route('public.sermons'),
                'has_video' => true,
                'has_audio' => true,
                'has_pdf' => true,
                'has_text' => true,
            ];
        })->all();

        return $section;
    }

    /** @return list<array<string, mixed>> */
    private function fallbackSermonCards(): array
    {
        return [
            [
                'title' => 'Walking Closer With Jesus Every Day',
                'description' => 'Discover practical steps to deepen your prayer life, trust God\'s promises, and live boldly as a disciple of Christ.',
                'image_url' => $this->siteAsset('img/sermon-1.jpg'),
                'date_label' => '13 Nov 2023',
                'author' => 'Admin',
                'link' => $this->legacyUrl('sermon-library/'),
                'has_video' => true,
                'has_audio' => true,
                'has_pdf' => true,
                'has_text' => true,
            ],
            [
                'title' => 'The Power of Grace in Every Season',
                'description' => 'God\'s grace sustains us through trials and triumphs. This message invites you to rest in His finished work and rise in renewed hope.',
                'image_url' => $this->siteAsset('img/sermon-2.jpg'),
                'date_label' => '08 Jun 2026',
                'author' => 'Pastor',
                'link' => $this->legacyUrl('sermon-library/'),
                'has_video' => true,
                'has_audio' => true,
                'has_pdf' => true,
                'has_text' => true,
            ],
            [
                'title' => 'Building Your Home on the Rock of Christ',
                'description' => 'Strong families are rooted in faith. Learn biblical principles for love, unity, and raising children who honor God.',
                'image_url' => $this->siteAsset('img/sermon-3.jpg'),
                'date_label' => '01 Jun 2026',
                'author' => 'Pastor',
                'link' => $this->legacyUrl('sermon-library/'),
                'has_video' => true,
                'has_audio' => true,
                'has_pdf' => true,
                'has_text' => true,
            ],
        ];
    }

    /** @return array{settings: array<string, string>, featured: ?array<string, mixed>, members: list<array<string, mixed>>} */
    private function team(): array
    {
        $settings = [
            'eyebrow' => 'Our Team',
            'title' => 'Meet Our Church Leadership',
        ];

        if (! Schema::hasTable('site_team_members')) {
            return ['settings' => $settings, 'featured' => null, 'members' => []];
        }

        $rows = DB::table('site_team_members')
            ->where('site', 'ag')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $featured = null;
        $members = [];
        foreach ($rows as $member) {
            if (! empty($member['photo_url'])) {
                $member['photo_url'] = $this->siteAsset((string) $member['photo_url']);
            } elseif (! empty($member['photo_path'])) {
                $member['photo_url'] = $this->siteAsset((string) $member['photo_path']);
            }
            if (($member['member_type'] ?? '') === 'featured') {
                $featured = $member;
            } else {
                $members[] = $member;
            }
        }

        return [
            'settings' => $settings,
            'featured' => $featured,
            'members' => $members,
        ];
    }
}
