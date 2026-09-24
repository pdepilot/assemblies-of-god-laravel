<?php

namespace App\Services\Website;

use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PromotionReadService
{
    public function __construct(
        private readonly PublicAssetResolver $assets,
        private readonly PublicHomepageReadService $homepage,
    ) {}

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        if (! Schema::hasTable('website_promotions')) {
            return [];
        }

        $rows = DB::table('website_promotions')
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn ($row) => $this->present((array) $row))->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        if (! Schema::hasTable('website_promotions') || $id < 1) {
            return null;
        }

        $row = DB::table('website_promotions')->where('id', $id)->first();

        return $row ? $this->present((array) $row) : null;
    }

    /**
     * Highest-priority promotion that should appear on first public-page paint.
     *
     * @return array<string, mixed>|null
     */
    public function activeBanner(): ?array
    {
        if (! Schema::hasTable('website_promotions')) {
            return null;
        }

        $now = now();
        $query = DB::table('website_promotions')->where('is_active', true);

        // Programme dates are for the event card, not a delay. Hide only after it ends.
        $query->where(function ($inner) use ($now) {
            $inner->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
        });

        $row = $query->orderByDesc('sort_order')->orderByDesc('id')->first();

        return $row ? $this->present((array) $row) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function present(array $row): array
    {
        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $ctaUrl = trim((string) ($row['cta_url'] ?? ''));

        $row['image_url'] = $imagePath !== '' ? $this->assets->url($imagePath) : null;
        $row['cta_href'] = $this->publicCtaHref($ctaUrl);
        $row['schedule_label'] = $this->scheduleLabel($row['starts_at'] ?? null, $row['ends_at'] ?? null);
        $row['is_active'] = ! empty($row['is_active']) && (string) $row['is_active'] !== '0';
        $row['show_every_visit'] = ! empty($row['show_every_visit']) && (string) $row['show_every_visit'] !== '0';
        $row['dismiss_key'] = 'ag-promo-'.$row['id'].'-'.substr(sha1((string) ($row['updated_at'] ?? '')), 0, 12);

        return $row;
    }

    private function publicCtaHref(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $lower = strtolower($raw);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $raw) === 1 || str_starts_with($raw, '/')) {
            return $raw;
        }

        return $this->homepage->legacyUrl($raw);
    }

    private function scheduleLabel(mixed $startsAt, mixed $endsAt): string
    {
        $start = $this->parseDate($startsAt);
        $end = $this->parseDate($endsAt);
        if ($start === null && $end === null) {
            return '';
        }
        if ($start !== null && $end !== null) {
            if ($start->isSameDay($end)) {
                return $start->format('l, j F Y · g:i A');
            }

            return $start->format('j M Y, g:i A').' – '.$end->format('j M Y, g:i A');
        }

        return ($start ?? $end)->format('l, j F Y · g:i A');
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
