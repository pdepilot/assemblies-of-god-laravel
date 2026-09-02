<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SaveAgHeroRequest;
use App\Http\Requests\Website\SaveWebsitePageRequest;
use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\Website\SeoReadService;
use App\Services\Website\SeoWriteService;
use App\Services\Website\WebsitePagesReadService;
use App\Services\Website\WebsitePagesWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class PagesController
{
    public function __construct(
        private readonly WebsitePagesReadService $read,
        private readonly WebsitePagesWriteService $write,
        private readonly SeoReadService $seoRead,
        private readonly SeoWriteService $seoWrite,
        private readonly WebsitePolicy $policy,
        private readonly PublicAssetResolver $assets,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        return view('website.pages.index', [
            'bootstrap' => $this->read->getBootstrap(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function edit(string $pageKey): View
    {
        $this->policy->requireManageWebsite($this->admin());
        abort_unless($this->read->isEditablePage($pageKey), 404);

        $page = $this->read->getPage($pageKey);
        $catalog = $this->read->pageCatalog()[$pageKey];
        $heroPath = trim((string) ($page['hero_image'] ?? ''));
        $seo = $this->seoRead->forKey($pageKey === 'home' ? 'home' : $pageKey);
        $seoOg = trim((string) ($seo['og_image'] ?? ''));

        return view('website.pages.edit', [
            'pageKey' => $pageKey,
            'catalog' => $catalog,
            'page' => $page,
            'heroImageUrl' => $heroPath !== '' ? $this->assets->url($heroPath) : null,
            'seo' => $seo,
            'seoOgImageUrl' => $seoOg !== '' ? $this->assets->url($seoOg) : null,
            'related' => $catalog['related'] ?? [],
        ]);
    }

    public function update(SaveWebsitePageRequest $request, string $pageKey): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        abort_unless($this->read->isEditablePage($pageKey), 404);

        try {
            $this->write->savePageOverride(
                $pageKey,
                $request->safe()->except([
                    'hero_image',
                    'remove_hero_image',
                    'seo_title',
                    'seo_meta_description',
                    'seo_og_image',
                    'remove_seo_og_image',
                ]),
                $request->file('hero_image'),
                $request->boolean('remove_hero_image'),
            );

            $this->seoWrite->savePage(
                [
                    'key' => $pageKey === 'home' ? 'home' : $pageKey,
                    'site' => 'ag',
                    'title' => (string) $request->input('seo_title', ''),
                    'meta_description' => (string) $request->input('seo_meta_description', ''),
                ],
                $request->file('seo_og_image'),
                $request->boolean('remove_seo_og_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['page' => $e->getMessage()]);
        }

        return redirect()
            ->route('website.pages.edit', $pageKey)
            ->with('status', '“'.($this->read->pageCatalog()[$pageKey]['label'] ?? $pageKey).'” saved.');
    }

    public function editHero(): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $bootstrap = $this->read->getBootstrap();
        $hero = $bootstrap['ag']['hero'] ?? [];
        $backgroundPath = trim((string) ($hero['background_image'] ?? ''));

        return view('website.pages.edit-hero', [
            'hero' => $hero,
            'backgroundImageUrl' => $backgroundPath !== ''
                ? $this->assets->url($backgroundPath)
                : null,
        ]);
    }

    public function updateHero(SaveAgHeroRequest $request): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        try {
            $this->write->saveAgHero(
                $request->safe()->except(['background_image', 'remove_background_image']),
                $request->file('background_image'),
                $request->boolean('remove_background_image'),
                (int) $this->admin()->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['background_image' => $e->getMessage()]);
        }

        return redirect()->route('website.pages.edit', 'home')->with('status', 'Homepage hero saved.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
