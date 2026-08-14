<?php

namespace App\Http\Controllers\Website;

use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\Website\SeoReadService;
use App\Services\Website\SeoWriteService;
use App\Services\Website\WebsitePagesReadService;
use App\Services\Website\WebsitePagesWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class SeoController
{
    public function __construct(
        private readonly SeoReadService $read,
        private readonly SeoWriteService $write,
        private readonly WebsitePagesReadService $pages,
        private readonly WebsitePagesWriteService $pagesWrite,
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
        $resolved = $this->read->forKey($pageKey);
        $catalog = $this->pages->pageCatalog()[$pageKey] ?? null;
        $publicUrl = (string) ($resolved['canonical'] ?? url('/'.$pageKey));
        $canEditChrome = $this->canEditChrome($pageKey, $catalog);
        $contentPage = $canEditChrome ? $this->pages->getPage($pageKey) : null;
        $contentEditRoute = is_array($catalog) && $this->pages->isEditablePage($pageKey)
            ? route('website.pages.edit', $pageKey)
            : null;

        return view('website.seo.edit', [
            'page' => array_replace($page, [
                'title' => $page['title'] ?? $resolved['title'] ?? '',
                'meta_description' => $page['meta_description'] ?? $resolved['meta_description'] ?? '',
                'include_in_sitemap' => $page['include_in_sitemap'] ?? true,
                'robots_notes' => $page['robots_notes'] ?? '',
            ]),
            'pageKey' => $pageKey,
            'publicUrl' => $publicUrl,
            'contentEditRoute' => $contentEditRoute,
            'canEditChrome' => $canEditChrome,
            'contentPage' => $contentPage,
            'ogImageUrl' => $ogPath !== '' ? $this->assets->url($ogPath) : null,
        ]);
    }

    public function update(Request $request, string $pageKey): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        $catalog = $this->pages->pageCatalog()[$pageKey] ?? null;
        $canEditChrome = $this->canEditChrome($pageKey, $catalog);

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'include_in_sitemap' => ['nullable', 'boolean'],
            'robots_notes' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_og_image' => ['nullable', 'boolean'],
        ];
        if ($canEditChrome) {
            $rules['heading'] = ['required', 'string', 'max:255'];
            $rules['eyebrow'] = ['nullable', 'string', 'max:120'];
            $rules['intro'] = ['nullable', 'string', 'max:2000'];
        }

        $request->validate($rules);

        try {
            if ($canEditChrome) {
                $current = $this->pages->getPage($pageKey);
                $this->pagesWrite->savePageOverride($pageKey, [
                    'heading' => (string) $request->input('heading', ''),
                    'eyebrow' => (string) $request->input('eyebrow', ''),
                    'intro' => (string) $request->input('intro', ''),
                    'body_html' => (string) ($current['body_html'] ?? ''),
                    'cta_label' => (string) ($current['cta_label'] ?? ''),
                    'cta_url' => (string) ($current['cta_url'] ?? ''),
                ]);
            }

            $this->write->savePage(
                [
                    'key' => $pageKey,
                    'site' => 'ag',
                    'title' => (string) $request->input('title', ''),
                    'meta_description' => (string) $request->input('meta_description', ''),
                    'include_in_sitemap' => $request->boolean('include_in_sitemap'),
                    'robots_notes' => (string) $request->input('robots_notes', ''),
                ],
                $request->file('og_image'),
                $request->boolean('remove_og_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['page' => $e->getMessage()]);
        }

        return redirect()
            ->route('website.seo.edit', $pageKey)
            ->with('status', 'Saved. Hero and SEO changes are live on the public page.');
    }

    /** @param array<string, mixed>|null $catalog */
    private function canEditChrome(string $pageKey, ?array $catalog): bool
    {
        if (! is_array($catalog) || ! $this->pages->isEditablePage($pageKey)) {
            return false;
        }

        return in_array((string) ($catalog['type'] ?? ''), ['content', 'header'], true);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
