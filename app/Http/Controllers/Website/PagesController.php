<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SaveAgHeroRequest;
use App\Http\Requests\Website\SaveWebsitePageRequest;
use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\PublicSite\PublicAssetResolver;
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

        return view('website.pages.edit', [
            'pageKey' => $pageKey,
            'catalog' => $catalog,
            'page' => $page,
            'heroImageUrl' => $heroPath !== '' ? $this->assets->url($heroPath) : null,
        ]);
    }

    public function update(SaveWebsitePageRequest $request, string $pageKey): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        abort_unless($this->read->isEditablePage($pageKey), 404);

        try {
            $this->write->savePageOverride(
                $pageKey,
                $request->safe()->except(['hero_image', 'remove_hero_image']),
                $request->file('hero_image'),
                $request->boolean('remove_hero_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['hero_image' => $e->getMessage()]);
        }

        return redirect()
            ->route('website.pages.index')
            ->with('status', 'Page “'.$pageKey.'” saved.');
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
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['background_image' => $e->getMessage()]);
        }

        return redirect()->route('website.pages.index')->with('status', 'Homepage hero saved.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
