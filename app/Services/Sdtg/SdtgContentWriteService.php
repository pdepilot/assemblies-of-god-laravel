<?php

namespace App\Services\Sdtg;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class SdtgContentWriteService
{
    private const MAX_IMAGE_BYTES = 5_242_880;

    private const MAX_VIDEO_BYTES = 52_428_800;

    /** @var array<string, string> */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var array<string, string> */
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    public function __construct(
        private readonly SdtgContentReadService $read,
    ) {}

    /**
     * Persist a single content section after applying media uploads and sanitizing.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, UploadedFile|null>  $uploads
     * @param  array<string, bool>  $removals
     * @return array<string, mixed>
     */
    public function saveSection(
        string $sectionKey,
        array $content,
        int $adminId,
        array $uploads = [],
        array $removals = [],
    ): array {
        if (! $this->read->isKnownSection($sectionKey)) {
            throw new InvalidArgumentException("Unknown SDTG content section [{$sectionKey}].");
        }

        $current = $this->read->getSection($sectionKey);
        $content = $this->applyMediaFields($sectionKey, null, $content, $current, $uploads, $removals);

        return $this->persist($sectionKey, $this->sanitizeSection($sectionKey, $content), $adminId);
    }

    /**
     * @return array<string, mixed>
     */
    public function resetSection(string $sectionKey, int $adminId): array
    {
        if (! $this->read->isKnownSection($sectionKey)) {
            throw new InvalidArgumentException("Unknown SDTG content section [{$sectionKey}].");
        }

        $current = $this->read->getSection($sectionKey);
        foreach ($this->mediaFieldKinds($sectionKey, null) as $field => $_kind) {
            $this->deleteUploadedMedia(trim((string) ($current[$field] ?? '')));
        }

        if (($this->read->sectionCatalog()[$sectionKey]['type'] ?? '') === 'nested') {
            foreach ($this->read->tabsFor($sectionKey) as $tab) {
                $sub = is_array($current[$tab] ?? null) ? $current[$tab] : [];
                foreach ($this->mediaFieldKinds($sectionKey, $tab) as $field => $_kind) {
                    $this->deleteUploadedMedia(trim((string) ($sub[$field] ?? '')));
                }
            }
        }

        return $this->persist($sectionKey, $this->read->defaultSection($sectionKey), $adminId);
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, UploadedFile|null>  $uploads
     * @param  array<string, bool>  $removals
     * @return array<string, mixed>
     */
    public function saveSubsection(
        string $sectionKey,
        string $subsection,
        array $content,
        int $adminId,
        array $uploads = [],
        array $removals = [],
    ): array {
        if (! $this->read->isKnownSection($sectionKey)) {
            throw new InvalidArgumentException("Unknown SDTG content section [{$sectionKey}].");
        }

        $tabs = $this->read->tabsFor($sectionKey);
        if ($tabs === [] || ! in_array($subsection, $tabs, true)) {
            throw new InvalidArgumentException("Unknown SDTG content subsection [{$subsection}] for [{$sectionKey}].");
        }

        $current = $this->read->getSection($sectionKey);
        $currentSub = is_array($current[$subsection] ?? null) ? $current[$subsection] : [];
        $content = $this->applyMediaFields($sectionKey, $subsection, $content, $currentSub, $uploads, $removals);
        $current[$subsection] = $content;

        return $this->persist($sectionKey, $this->sanitizeSection($sectionKey, $current), $adminId);
    }

    /**
     * @return array<string, 'image'|'video'>
     */
    public function mediaFieldKinds(string $sectionKey, ?string $tab): array
    {
        return match (true) {
            $sectionKey === 'hero' && $tab === null => [
                'video_url' => 'video',
                'image' => 'image',
            ],
            $sectionKey === 'about' && $tab === null => [
                'image' => 'image',
            ],
            $sectionKey === 'livestream_teaser' && $tab === null => [
                'preview_image' => 'image',
            ],
            $sectionKey === 'livestream_page' && $tab === 'hero' => [
                'video' => 'video',
                'poster' => 'image',
            ],
            $sectionKey === 'livestream_page' && $tab === 'cta' => [
                'image' => 'image',
            ],
            $sectionKey === 'livestream_page' && $tab === 'current_session' => [
                'photo' => 'image',
            ],
            $sectionKey === 'donate_page' && $tab === 'hero' => [
                'image' => 'image',
            ],
            $sectionKey === 'donate_page' && $tab === 'cta' => [
                'image' => 'image',
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function persist(string $sectionKey, array $content, int $adminId): array
    {
        $json = json_encode($content, JSON_UNESCAPED_UNICODE);
        $existing = DB::table('sdtg_site_content')->where('section_key', $sectionKey)->first();

        $payload = [
            'content_json' => $json,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('sdtg_site_content')->where('section_key', $sectionKey)->update($payload);
        } else {
            DB::table('sdtg_site_content')->insert($payload + [
                'section_key' => $sectionKey,
                'created_at' => now(),
            ]);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $current
     * @param  array<string, UploadedFile|null>  $uploads
     * @param  array<string, bool>  $removals
     * @return array<string, mixed>
     */
    private function applyMediaFields(
        string $sectionKey,
        ?string $tab,
        array $content,
        array $current,
        array $uploads,
        array $removals,
    ): array {
        foreach ($this->mediaFieldKinds($sectionKey, $tab) as $field => $kind) {
            $path = trim((string) ($current[$field] ?? ''));

            if (! empty($removals[$field])) {
                $this->deleteUploadedMedia($path);
                $path = '';
            }

            $file = $uploads[$field] ?? null;
            if ($file instanceof UploadedFile) {
                $uploaded = $kind === 'video'
                    ? $this->storeVideo($file)
                    : $this->storeImage($file);
                $this->deleteUploadedMedia($path);
                $path = $uploaded;
            }

            $content[$field] = $path;
        }

        return $content;
    }

    private function storeImage(UploadedFile $image): string
    {
        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Image must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Image must be 5 MB or smaller.');
        }

        return $this->storeUploadedFile($image, self::ALLOWED_IMAGE_TYPES[$image->getMimeType()]);
    }

    private function storeVideo(UploadedFile $video): string
    {
        if (! isset(self::ALLOWED_VIDEO_TYPES[$video->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Video must be MP4, WebM, or MOV.');
        }
        if ($video->getSize() > self::MAX_VIDEO_BYTES) {
            throw new InvalidArgumentException('Video must be 50 MB or smaller.');
        }

        return $this->storeUploadedFile($video, self::ALLOWED_VIDEO_TYPES[$video->getMimeType()]);
    }

    private function storeUploadedFile(UploadedFile $file, string $ext): string
    {
        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/site-content/'.$filename;
        $absoluteDir = public_path('site/sdgt/uploads/site-content');

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        if (! $file->move($absoluteDir, $filename)) {
            throw new RuntimeException('Unable to save uploaded media.');
        }

        return $relativePath;
    }

    private function deleteUploadedMedia(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/site-content/')) {
            return;
        }

        $absolute = public_path('site/sdgt/'.$path);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function sanitizeSection(string $key, array $value): array
    {
        return match ($key) {
            'hero' => $this->sanitizeHero($value),
            'stats' => ['items' => $this->sanitizeStatItems($value['items'] ?? [])],
            'about' => [
                'eyebrow' => trim((string) ($value['eyebrow'] ?? '')),
                'heading' => trim((string) ($value['heading'] ?? '')),
                'lead' => trim((string) ($value['lead'] ?? '')),
                'image' => trim((string) ($value['image'] ?? '')),
                'badge_number' => trim((string) ($value['badge_number'] ?? '')),
                'badge_text' => trim((string) ($value['badge_text'] ?? '')),
                'blocks' => $this->sanitizeIconCards($value['blocks'] ?? []),
            ],
            'why_attend' => [
                'eyebrow' => trim((string) ($value['eyebrow'] ?? '')),
                'heading' => trim((string) ($value['heading'] ?? '')),
                'cards' => $this->sanitizeIconCards($value['cards'] ?? []),
            ],
            'donate' => [
                'eyebrow' => trim((string) ($value['eyebrow'] ?? '')),
                'heading' => trim((string) ($value['heading'] ?? '')),
                'description' => trim((string) ($value['description'] ?? '')),
                'cta_label' => trim((string) ($value['cta_label'] ?? '')),
                'cta_link' => trim((string) ($value['cta_link'] ?? '')),
                'cards' => $this->sanitizeIconCards($value['cards'] ?? []),
            ],
            'livestream_teaser' => [
                'eyebrow' => trim((string) ($value['eyebrow'] ?? '')),
                'heading' => trim((string) ($value['heading'] ?? '')),
                'description' => trim((string) ($value['description'] ?? '')),
                'youtube_url' => trim((string) ($value['youtube_url'] ?? '')),
                'facebook_url' => trim((string) ($value['facebook_url'] ?? '')),
                'cta_label' => trim((string) ($value['cta_label'] ?? '')),
                'cta_link' => trim((string) ($value['cta_link'] ?? '')),
                'preview_image' => trim((string) ($value['preview_image'] ?? '')),
            ],
            'donate_page' => $this->sanitizeDonatePageContent($value),
            'livestream_page' => $this->sanitizeLivestreamPageContent($value),
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, string>
     */
    private function sanitizeHero(array $value): array
    {
        $fields = ['eyebrow', 'title_line1', 'title_line2', 'subtitle', 'primary_label', 'primary_link', 'secondary_label', 'secondary_link', 'video_url', 'image'];
        $clean = [];
        foreach ($fields as $field) {
            $clean[$field] = trim((string) ($value[$field] ?? ''));
        }

        return $clean;
    }

    /**
     * Normalise a repeatable list of icon/title/text cards.
     *
     * @return list<array{icon: string, title: string, text: string}>
     */
    private function sanitizeIconCards(mixed $raw): array
    {
        $cards = [];
        if (! is_array($raw)) {
            return $cards;
        }

        foreach ($raw as $card) {
            if (! is_array($card)) {
                continue;
            }
            $title = trim((string) ($card['title'] ?? ''));
            $text = trim((string) ($card['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $cards[] = [
                'icon' => trim((string) ($card['icon'] ?? 'fa-star')) ?: 'fa-star',
                'title' => $title,
                'text' => $text,
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{target: int, suffix: string, label: string}>
     */
    private function sanitizeStatItems(mixed $raw): array
    {
        $items = [];
        if (! is_array($raw)) {
            return $items;
        }
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $items[] = [
                'icon' => trim((string) ($item['icon'] ?? 'fa-star')) ?: 'fa-star',
                'target' => max(0, (int) ($item['target'] ?? 0)),
                'suffix' => trim((string) ($item['suffix'] ?? '')),
                'label' => $label,
            ];
        }

        return $items;
    }

    /**
     * A variant of stat items that omits the icon field (used by donate_page map/transparency).
     *
     * @return list<array{target: int, suffix: string, label: string}>
     */
    private function sanitizePlainStatItems(mixed $raw): array
    {
        $items = [];
        if (! is_array($raw)) {
            return $items;
        }
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $items[] = [
                'target' => max(0, (int) ($item['target'] ?? 0)),
                'suffix' => trim((string) ($item['suffix'] ?? '')),
                'label' => $label,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    private function sanitizeBankAccount(mixed $raw, bool $international): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $account = [
            'bank' => trim((string) ($raw['bank'] ?? '')),
            'account_name' => trim((string) ($raw['account_name'] ?? '')),
            'account_number' => trim((string) ($raw['account_number'] ?? '')),
        ];
        if ($international) {
            $account['swift'] = trim((string) ($raw['swift'] ?? ''));
            $account['iban'] = trim((string) ($raw['iban'] ?? ''));
        }

        return $account;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function sanitizeDonatePageContent(array $value): array
    {
        $defaults = $this->read->defaultSection('donate_page');
        $hero = is_array($value['hero'] ?? null) ? $value['hero'] : [];
        $impact = is_array($value['impact'] ?? null) ? $value['impact'] : [];
        $give = is_array($value['give'] ?? null) ? $value['give'] : [];
        $donorWall = is_array($value['donor_wall'] ?? null) ? $value['donor_wall'] : [];
        $map = is_array($value['map'] ?? null) ? $value['map'] : [];
        $feed = is_array($value['feed'] ?? null) ? $value['feed'] : [];
        $testimonials = is_array($value['testimonials'] ?? null) ? $value['testimonials'] : [];
        $transparency = is_array($value['transparency'] ?? null) ? $value['transparency'] : [];
        $trust = is_array($value['trust'] ?? null) ? $value['trust'] : [];
        $cta = is_array($value['cta'] ?? null) ? $value['cta'] : [];

        $impactCards = [];
        foreach ($impact['cards'] ?? [] as $card) {
            if (! is_array($card)) {
                continue;
            }
            $title = trim((string) ($card['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $impactCards[] = [
                'icon' => trim((string) ($card['icon'] ?? 'fa-star')) ?: 'fa-star',
                'title' => $title,
                'text' => trim((string) ($card['text'] ?? '')),
                'progress' => max(0, min(100, (int) ($card['progress'] ?? 0))),
            ];
        }

        $categories = [];
        foreach ($give['categories'] ?? [] as $cat) {
            if (! is_array($cat)) {
                continue;
            }
            $key = strtolower(trim((string) ($cat['key'] ?? '')));
            $title = trim((string) ($cat['title'] ?? ''));
            if ($key === '' || $title === '') {
                continue;
            }
            $categories[] = [
                'key' => preg_replace('/[^a-z0-9_-]/', '', $key) ?: $key,
                'icon' => trim((string) ($cat['icon'] ?? 'fa-star')) ?: 'fa-star',
                'title' => $title,
                'nigeria' => $this->sanitizeBankAccount($cat['nigeria'] ?? [], false),
                'international' => $this->sanitizeBankAccount($cat['international'] ?? [], true),
            ];
        }

        $donors = [];
        foreach ($donorWall['donors'] ?? [] as $donor) {
            if (! is_array($donor)) {
                continue;
            }
            $name = trim((string) ($donor['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $donors[] = [
                'name' => $name,
                'meta' => trim((string) ($donor['meta'] ?? '')),
                'amount' => trim((string) ($donor['amount'] ?? '')),
                'purpose' => trim((string) ($donor['purpose'] ?? '')),
                'phone' => trim((string) ($donor['phone'] ?? '')),
            ];
        }

        $feedItems = [];
        foreach ($feed['items'] ?? [] as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $feedItems[] = $text;
            }
        }

        $testItems = [];
        foreach ($testimonials['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $quote = trim((string) ($item['quote'] ?? ''));
            if ($quote === '') {
                continue;
            }
            $testItems[] = [
                'image' => trim((string) ($item['image'] ?? '')),
                'quote' => $quote,
                'author' => trim((string) ($item['author'] ?? '')),
                'location' => trim((string) ($item['location'] ?? '')),
            ];
        }

        return [
            'hero' => [
                'badge' => trim((string) ($hero['badge'] ?? '')),
                'title_before' => trim((string) ($hero['title_before'] ?? '')),
                'title_highlight' => trim((string) ($hero['title_highlight'] ?? '')),
                'subtitle' => trim((string) ($hero['subtitle'] ?? '')),
                'primary_label' => trim((string) ($hero['primary_label'] ?? '')),
                'primary_link' => trim((string) ($hero['primary_link'] ?? '')),
                'secondary_label' => trim((string) ($hero['secondary_label'] ?? '')),
                'secondary_link' => trim((string) ($hero['secondary_link'] ?? '')),
                'image' => trim((string) ($hero['image'] ?? '')),
            ],
            'impact' => [
                'eyebrow' => trim((string) ($impact['eyebrow'] ?? '')),
                'heading' => trim((string) ($impact['heading'] ?? '')),
                'cards' => $impactCards ?: ($defaults['impact']['cards'] ?? []),
            ],
            'give' => [
                'eyebrow' => trim((string) ($give['eyebrow'] ?? '')),
                'heading' => trim((string) ($give['heading'] ?? '')),
                'description' => trim((string) ($give['description'] ?? '')),
                'categories' => $categories ?: ($defaults['give']['categories'] ?? []),
            ],
            'donor_wall' => [
                'eyebrow' => trim((string) ($donorWall['eyebrow'] ?? '')),
                'heading' => trim((string) ($donorWall['heading'] ?? '')),
                'description' => trim((string) ($donorWall['description'] ?? '')),
                'donors' => $donors,
            ],
            'map' => [
                'eyebrow' => trim((string) ($map['eyebrow'] ?? '')),
                'heading' => trim((string) ($map['heading'] ?? '')),
                'stats' => $this->sanitizePlainStatItems($map['stats'] ?? []),
            ],
            'feed' => [
                'items' => $feedItems,
            ],
            'testimonials' => [
                'eyebrow' => trim((string) ($testimonials['eyebrow'] ?? '')),
                'heading' => trim((string) ($testimonials['heading'] ?? '')),
                'items' => $testItems,
            ],
            'transparency' => [
                'eyebrow' => trim((string) ($transparency['eyebrow'] ?? '')),
                'heading' => trim((string) ($transparency['heading'] ?? '')),
                'stats' => $this->sanitizePlainStatItems($transparency['stats'] ?? []),
            ],
            'trust' => [
                'items' => $this->sanitizeIconCards($trust['items'] ?? []),
            ],
            'cta' => [
                'title' => trim((string) ($cta['title'] ?? '')),
                'subtitle' => trim((string) ($cta['subtitle'] ?? '')),
                'primary_label' => trim((string) ($cta['primary_label'] ?? '')),
                'primary_link' => trim((string) ($cta['primary_link'] ?? '')),
                'secondary_label' => trim((string) ($cta['secondary_label'] ?? '')),
                'secondary_link' => trim((string) ($cta['secondary_link'] ?? '')),
                'image' => trim((string) ($cta['image'] ?? '')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function sanitizeLivestreamPageContent(array $value): array
    {
        $defaults = $this->read->defaultSection('livestream_page');
        $hero = is_array($value['hero'] ?? null) ? $value['hero'] : [];
        $streams = is_array($value['streams'] ?? null) ? $value['streams'] : [];
        $current = is_array($value['current_session'] ?? null) ? $value['current_session'] : [];
        $upcoming = is_array($value['upcoming_session'] ?? null) ? $value['upcoming_session'] : [];
        $cta = is_array($value['cta'] ?? null) ? $value['cta'] : [];

        $schedule = [];
        foreach ($value['schedule'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $session = trim((string) ($row['session'] ?? ''));
            if ($session === '') {
                continue;
            }
            $schedule[] = [
                'time' => trim((string) ($row['time'] ?? '')),
                'session' => $session,
                'speaker' => trim((string) ($row['speaker'] ?? '')),
                'worship' => trim((string) ($row['worship'] ?? '')),
            ];
        }

        $broadcasts = [];
        foreach ($value['past_broadcasts'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $type = trim((string) ($row['type'] ?? 'youtube'));
            if (! in_array($type, ['local', 'youtube', 'vimeo'], true)) {
                $type = 'youtube';
            }
            $broadcasts[] = [
                'title' => $title,
                'year' => trim((string) ($row['year'] ?? '')),
                'duration' => trim((string) ($row['duration'] ?? '')),
                'thumb' => trim((string) ($row['thumb'] ?? '')),
                'video' => trim((string) ($row['video'] ?? '')),
                'type' => $type,
            ];
        }

        $social = [];
        foreach ($value['social'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $social[] = [
                'platform' => trim((string) ($row['platform'] ?? 'link')),
                'icon' => trim((string) ($row['icon'] ?? 'fas fa-link')),
                'label' => $label,
                'subtitle' => trim((string) ($row['subtitle'] ?? '')),
                'url' => trim((string) ($row['url'] ?? '')),
            ];
        }

        $sanitizeStream = static function (mixed $raw): array {
            if (! is_array($raw)) {
                return [];
            }
            $out = [];
            foreach (['id', 'label', 'embed', 'url'] as $field) {
                if (isset($raw[$field])) {
                    $out[$field] = trim((string) $raw[$field]);
                }
            }

            return $out;
        };

        $streamBlock = [
            'youtube' => $sanitizeStream($streams['youtube'] ?? []),
            'facebook' => $sanitizeStream($streams['facebook'] ?? []),
            'vimeo' => $sanitizeStream($streams['vimeo'] ?? []),
            'custom' => $sanitizeStream($streams['custom'] ?? []),
            'default_platform' => trim((string) ($streams['default_platform'] ?? 'youtube')) ?: 'youtube',
        ];

        return [
            'hero' => [
                'badge' => trim((string) ($hero['badge'] ?? '')),
                'title_before' => trim((string) ($hero['title_before'] ?? '')),
                'title_highlight' => trim((string) ($hero['title_highlight'] ?? '')),
                'subtitle' => trim((string) ($hero['subtitle'] ?? '')),
                'primary_label' => trim((string) ($hero['primary_label'] ?? '')),
                'primary_link' => trim((string) ($hero['primary_link'] ?? '')),
                'secondary_label' => trim((string) ($hero['secondary_label'] ?? '')),
                'secondary_link' => trim((string) ($hero['secondary_link'] ?? '')),
                'tertiary_label' => trim((string) ($hero['tertiary_label'] ?? '')),
                'tertiary_link' => trim((string) ($hero['tertiary_link'] ?? '')),
                'video' => trim((string) ($hero['video'] ?? '')),
                'poster' => trim((string) ($hero['poster'] ?? '')),
            ],
            'streams' => $streamBlock,
            'current_session' => [
                'title' => trim((string) ($current['title'] ?? '')),
                'minister' => trim((string) ($current['minister'] ?? '')),
                'ministry' => trim((string) ($current['ministry'] ?? '')),
                'country' => trim((string) ($current['country'] ?? '')),
                'worship_team' => trim((string) ($current['worship_team'] ?? '')),
                'photo' => trim((string) ($current['photo'] ?? '')),
                'topic' => trim((string) ($current['topic'] ?? '')),
            ],
            'upcoming_session' => [
                'title' => trim((string) ($upcoming['title'] ?? '')),
                'minister' => trim((string) ($upcoming['minister'] ?? '')),
                'time' => trim((string) ($upcoming['time'] ?? '')),
            ],
            'schedule' => $schedule ?: ($defaults['schedule'] ?? []),
            'past_broadcasts' => $broadcasts,
            'social' => $social ?: ($defaults['social'] ?? []),
            'cta' => [
                'title' => trim((string) ($cta['title'] ?? '')),
                'subtitle' => trim((string) ($cta['subtitle'] ?? '')),
                'primary_label' => trim((string) ($cta['primary_label'] ?? '')),
                'secondary_label' => trim((string) ($cta['secondary_label'] ?? '')),
                'secondary_link' => trim((string) ($cta['secondary_link'] ?? '')),
                'tertiary_label' => trim((string) ($cta['tertiary_label'] ?? '')),
                'tertiary_link' => trim((string) ($cta['tertiary_link'] ?? '')),
                'image' => trim((string) ($cta['image'] ?? '')),
            ],
        ];
    }
}
