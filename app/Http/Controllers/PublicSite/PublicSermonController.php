<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Sermons\PublicSermonHydrator;
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
        private readonly PublicSermonHydrator $hydrator,
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
        $sermon = $this->hydrator->present($sermon);
        $related = $this->hydrator->presentMany($this->sermons->related($sermon, 3));
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

        $payload = $this->homepage->chrome();

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
}
