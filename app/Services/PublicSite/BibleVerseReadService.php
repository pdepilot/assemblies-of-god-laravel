<?php

namespace App\Services\PublicSite;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Random KJV verse picker for the homepage scripture rotator.
 * Source: public-domain KJV (resources/data/bible/kjv-verses.json).
 */
final class BibleVerseReadService
{
    private const MAX_QUOTE_CHARS = 280;

    private const DEFAULT_COUNT = 10;

    /**
     * @return list<array{quote: string, reference: string}>
     */
    public function randomVerses(int $count = self::DEFAULT_COUNT): array
    {
        $count = max(1, min(20, $count));
        $pool = $this->eligibleVerses();
        if ($pool === []) {
            return $this->fallbackVerses();
        }

        $keys = array_keys($pool);
        shuffle($keys);
        $selected = array_slice($keys, 0, min($count, count($keys)));

        $verses = [];
        foreach ($selected as $reference) {
            $verses[] = [
                'quote' => $pool[$reference],
                'reference' => $reference.' (KJV)',
            ];
        }

        return $verses;
    }

    /**
     * @return array<string, string> reference => cleaned quote
     */
    private function eligibleVerses(): array
    {
        return Cache::remember('bible.kjv.eligible_verses', 86400, function (): array {
            $path = resource_path('data/bible/kjv-verses.json');
            if (! File::exists($path)) {
                return [];
            }

            $decoded = json_decode((string) File::get($path), true);
            if (! is_array($decoded) || $decoded === []) {
                return [];
            }

            $eligible = [];
            foreach ($decoded as $reference => $text) {
                if (! is_string($reference) || ! is_string($text)) {
                    continue;
                }

                $quote = $this->cleanVerseText($text);
                if ($quote === '' || mb_strlen($quote) > self::MAX_QUOTE_CHARS) {
                    continue;
                }

                // Skip ultra-short fragments and verse numbers that are mostly punctuation.
                if (mb_strlen($quote) < 20) {
                    continue;
                }

                $eligible[$reference] = $quote;
            }

            return $eligible;
        });
    }

    private function cleanVerseText(string $text): string
    {
        $text = trim($text);
        // Paragraph marker used in some KJV JSON dumps.
        $text = ltrim($text, "# \t");
        // Remove italic markers like [was].
        $text = preg_replace('/\[([^\]]+)\]/', '$1', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<array{quote: string, reference: string}>
     */
    private function fallbackVerses(): array
    {
        return [
            [
                'quote' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.',
                'reference' => 'John 3:16 (KJV)',
            ],
            [
                'quote' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding.',
                'reference' => 'Proverbs 3:5 (KJV)',
            ],
            [
                'quote' => 'I can do all things through Christ which strengtheneth me.',
                'reference' => 'Philippians 4:13 (KJV)',
            ],
        ];
    }
}
