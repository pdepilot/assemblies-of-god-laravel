<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use App\Services\Website\TeamSectionReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\View\View;

final class LeadershipController extends Controller
{
    public function __construct(
        private readonly WebsitePagesReadService $pages,
        private readonly TeamSectionReadService $team,
        private readonly PublicHomepageReadService $homepage,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function show(): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $page = $this->pages->getPage('leadership');
        $members = $this->team->listMembers('ag');
        $payload = $this->homepage->payload();
        $seo = $this->seo->forKey('leadership', route('public.leadership'));
        if (trim((string) ($seo['title'] ?? '')) === '') {
            $seo['title'] = trim((string) ($page['heading'] ?? '')) !== ''
                ? (string) $page['heading'].' | '.$brandShortName
                : 'Leadership Team | '.$brandShortName;
        }

        return view('public.leadership', [
            'church' => $payload['church'],
            'page' => $page,
            'members' => $members,
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => array_merge(
                $this->schema->organizationAndChurch(),
                [$this->schema->breadcrumbs([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Leadership', 'url' => route('public.leadership')],
                ])],
            ),
            'testimonySourcePage' => 'leadership',
        ]);
    }
}
