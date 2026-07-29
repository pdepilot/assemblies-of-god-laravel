<?php

namespace App\Http\Controllers\Website;

use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\Website\SeoReadService;
use App\Services\Website\SeoWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class SeoController
{
    public function __construct(
        private readonly SeoReadService $read,
        private readonly SeoWriteService $write,
        private readonly WebsitePolicy $policy,
        private readonly PublicAssetResolver $assets,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        return view('website.seo.index', [
            'pages' => $this->read->listPages(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function edit(string $pageKey): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $page = collect($this->read->listPages())->firstWhere('key', $pageKey) ?? ['key' => $pageKey];
        $ogPath = trim((string) ($page['og_image'] ?? ''));

        return view('website.seo.edit', [
            'page' => $page,
            'ogImageUrl' => $ogPath !== '' ? $this->assets->url($ogPath) : null,
        ]);
    }

    public function update(Request $request, string $pageKey): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        try {
            $this->write->savePage(
                [
                    'key' => $pageKey,
                    'site' => 'ag',
                    'title' => (string) $request->input('title', ''),
                    'meta_description' => (string) $request->input('meta_description', ''),
                ],
                $request->file('og_image'),
                $request->boolean('remove_og_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['og_image' => $e->getMessage()]);
        }

        return redirect()->route('website.seo.index')->with('status', 'SEO page saved.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
