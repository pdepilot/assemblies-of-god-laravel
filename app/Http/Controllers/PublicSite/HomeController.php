<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly PublicHomepageReadService $homepage,
        private readonly WebsitePagesReadService $pages,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function index(): View
    {
        $payload = $this->homepage->payload();
        $homePage = $this->pages->getPage('home');

        $sermons = is_array($payload['sermons'] ?? null) ? $payload['sermons'] : [];
        if (trim((string) ($homePage['sermons_eyebrow'] ?? '')) !== '') {
            $sermons['eyebrow'] = (string) $homePage['sermons_eyebrow'];
        }
        if (trim((string) ($homePage['sermons_title'] ?? '')) !== '') {
            $sermons['title'] = (string) $homePage['sermons_title'];
        }

        $team = is_array($payload['team'] ?? null) ? $payload['team'] : ['settings' => [], 'featured' => null, 'members' => []];
        $teamSettings = is_array($team['settings'] ?? null) ? $team['settings'] : [];
        if (trim((string) ($homePage['team_eyebrow'] ?? '')) !== '') {
            $teamSettings['eyebrow'] = (string) $homePage['team_eyebrow'];
        }
        if (trim((string) ($homePage['team_title'] ?? '')) !== '') {
            $teamSettings['title'] = (string) $homePage['team_title'];
        }
        $team['settings'] = $teamSettings;

        return view('public.home', $payload + [
            'homePage' => $homePage,
            'sermons' => $sermons,
            'team' => $team,
            'seo' => $this->seo->forKey('home', url('/')),
            'schemaGraphs' => $this->schema->organizationAndChurch(),
            'testimonySourcePage' => 'index',
        ]);
    }
}
