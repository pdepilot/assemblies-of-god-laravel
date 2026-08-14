<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Sermons\SermonReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicSermonController extends Controller
{
    public function __construct(
        private readonly SermonReadService $sermons,
        private readonly PublicHomepageReadService $homepage,
        private readonly PublicAssetResolver $assets,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function show(string $slug): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $sermon = $this->sermons->getPublishedBySlug($slug);
        if ($sermon === null) {
            throw new NotFoundHttpException('Sermon not found.');
        }

        $this->sermons->incrementViews((int) $sermon['id']);
        $sermon = $this->hydrate($sermon);
        $related = array_map(fn (array $s) => $this->hydrate($s), $this->sermons->related($sermon, 3));
        $canonical = route('public.sermons.show', $slug);

        $seo = $this->seo->forKey('sermons', $canonical);
        $seoTitle = trim((string) ($sermon['seo_title'] ?? ''));
        $seo['title'] = ($seoTitle !== '' ? $seoTitle : (string) $sermon['title']).' | '.$brandShortName;
        if (trim((string) ($sermon['seo_description'] ?? '')) !== '') {
            $seo['meta_description'] = (string) $sermon['seo_description'];
        } elseif (trim((string) ($sermon['description'] ?? '')) !== '') {
            $seo['meta_description'] = \Illuminate\Support\Str::limit(strip_tags((string) $sermon['description']), 160, '');
        }
        if (trim((string) ($sermon['featured_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $sermon['featured_image'];
        }

        $payload = $this->homepage->payload();

        return view('public.sermons.show', [
            'church' => $payload['church'],
            'sermon' => $sermon,
            'related' => $related,
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => [
                $this->schema->sermon($sermon, $canonical, (string) ($sermon['image_url'] ?? '')),
                $this->schema->breadcrumbs([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Sermons', 'url' => route('public.sermons')],
                    ['name' => (string) $sermon['title'], 'url' => $canonical],
                ]),
            ],
            'bodyClass' => 'has-reading-progress',
            'testimonySourcePage' => 'sermons',
        ]);
    }

    /** @param array<string, mixed> $sermon @return array<string, mixed> */
    private function hydrate(array $sermon): array
    {
        $image = trim((string) ($sermon['featured_image'] ?? ''));
        $sermon['image_url'] = $image !== '' ? $this->assets->url($image) : $this->assets->url('images/main1.jpg');
        $sermon['date_display'] = ! empty($sermon['sermon_date'])
            ? date('M j, Y', strtotime((string) $sermon['sermon_date']))
            : '';
        $tags = $sermon['tags'] ?? [];
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : [];
        }
        $sermon['tags'] = is_array($tags) ? $tags : [];

        return $sermon;
    }
}
