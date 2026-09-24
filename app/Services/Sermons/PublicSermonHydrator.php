<?php

namespace App\Services\Sermons;

use App\Services\PublicSite\PublicAssetResolver;
use Illuminate\Support\Str;

final class PublicSermonHydrator
{
    public function __construct(
        private readonly PublicAssetResolver $assets,
    ) {}

    /** @param array<string, mixed> $sermon @return array<string, mixed> */
    public function present(array $sermon): array
    {
        $image = trim((string) ($sermon['featured_image'] ?? ''));
        $sermon['image_url'] = $image !== '' ? $this->assets->url($image) : $this->assets->url('img/sermon-1.jpg');
        $sermon['date_display'] = ! empty($sermon['sermon_date'])
            ? date('M j, Y', strtotime((string) $sermon['sermon_date']))
            : '';

        $tags = $sermon['tags'] ?? [];
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : [];
        }
        $sermon['tags'] = is_array($tags) ? $tags : [];

        $youtube = trim((string) ($sermon['youtube_url'] ?? ''));
        $vimeo = trim((string) ($sermon['vimeo_url'] ?? ''));
        $sermon['youtube_embed_url'] = $this->youtubeEmbed($youtube);
        $sermon['vimeo_embed_url'] = $this->vimeoEmbed($vimeo);

        $audioStream = trim((string) ($sermon['audio_stream_url'] ?? ''));
        $audioFile = trim((string) ($sermon['audio_file_path'] ?? ''));
        $sermon['audio_url'] = $audioStream !== '' ? $audioStream : ($audioFile !== '' ? $this->assets->url($audioFile) : '');

        $videoFile = trim((string) ($sermon['video_file_path'] ?? ''));
        $sermon['video_file_url'] = $videoFile !== '' ? $this->assets->url($videoFile) : '';

        $pdf = trim((string) ($sermon['pdf_file_path'] ?? ''));
        $sermon['pdf_url'] = $pdf !== '' ? $this->assets->url($pdf) : '';

        $sermon['has_video'] = $sermon['youtube_embed_url'] !== ''
            || $sermon['vimeo_embed_url'] !== ''
            || $sermon['video_file_url'] !== ''
            || trim((string) ($sermon['facebook_video_url'] ?? '')) !== ''
            || trim((string) ($sermon['video_embed_code'] ?? '')) !== '';
        $sermon['has_audio'] = $sermon['audio_url'] !== '';
        $sermon['has_pdf'] = $sermon['pdf_url'] !== '';
        $sermon['excerpt'] = Str::limit(strip_tags((string) ($sermon['description'] ?? $sermon['content_html'] ?? '')), 180);
        $slug = trim((string) ($sermon['slug'] ?? ''));
        $sermon['url'] = $slug !== '' ? route('public.sermons.show', $slug) : route('public.sermons');

        return $sermon;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function presentMany(array $items): array
    {
        return array_map(fn (array $item) => $this->present($item), $items);
    }

    private function youtubeEmbed(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (preg_match('#(?:youtube\.com/embed/)([A-Za-z0-9_-]{6,})#', $url, $m) === 1) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }
        if (preg_match('#(?:youtu\.be/|v=|shorts/)([A-Za-z0-9_-]{6,})#', $url, $m) === 1) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        return '';
    }

    private function vimeoEmbed(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m) === 1) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return '';
    }
}
