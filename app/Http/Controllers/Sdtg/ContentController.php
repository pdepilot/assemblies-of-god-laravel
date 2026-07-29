<?php

namespace App\Http\Controllers\Sdtg;

use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\Sdtg\SdtgContentReadService;
use App\Services\Sdtg\SdtgContentWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContentController
{
    public function __construct(
        private readonly SdtgContentReadService $read,
        private readonly SdtgContentWriteService $write,
        private readonly SdtgPolicy $policy,
        private readonly PublicAssetResolver $assets,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.content.index', [
            'sections' => $this->read->listCatalogSections(),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function edit(Request $request, string $section): View
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->ensureKnownSection($section);

        $catalog = $this->read->sectionCatalog();
        $meta = $catalog[$section];
        $content = $this->read->getSection($section);

        $activeTab = null;
        $tabs = [];
        if ($meta['type'] === 'nested') {
            $tabs = $this->read->tabsFor($section);
            $activeTab = (string) $request->query('tab', $tabs[0]);
            if (! in_array($activeTab, $tabs, true)) {
                $activeTab = $tabs[0];
            }
        }

        $mediaSlice = $meta['type'] === 'nested'
            ? (is_array($content[$activeTab] ?? null) ? $content[$activeTab] : [])
            : $content;

        return view('sdtg.content.edit', [
            'sectionKey' => $section,
            'meta' => $meta,
            'content' => $content,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'mediaUrls' => $this->resolveMediaUrls($section, $activeTab, $mediaSlice),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);
        $this->ensureKnownSection($section);

        $catalog = $this->read->sectionCatalog();
        $meta = $catalog[$section];
        $input = (array) $request->input('content', []);
        $tab = null;

        try {
            if ($meta['type'] === 'nested') {
                $tab = (string) $request->input('tab', '');
                $tabs = $this->read->tabsFor($section);
                if ($tab !== '' && in_array($tab, $tabs, true)) {
                    $this->write->saveSubsection(
                        $section,
                        $tab,
                        $input,
                        (int) $admin->id,
                        $this->collectUploads($request, $section, $tab),
                        $this->collectRemovals($request, $section, $tab),
                    );
                } else {
                    $tab = null;
                    $this->write->saveSection(
                        $section,
                        $input,
                        (int) $admin->id,
                        $this->collectUploads($request, $section, null),
                        $this->collectRemovals($request, $section, null),
                    );
                }
            } else {
                $this->write->saveSection(
                    $section,
                    $input,
                    (int) $admin->id,
                    $this->collectUploads($request, $section, null),
                    $this->collectRemovals($request, $section, null),
                );
            }
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('sdtg.content.edit', array_filter(['section' => $section, 'tab' => $request->input('tab')]))
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sdtg.content.edit', array_filter(['section' => $section, 'tab' => $tab]))
            ->with('status', $meta['label'].' saved.');
    }

    public function reset(Request $request, string $section): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);
        $this->ensureKnownSection($section);

        $catalog = $this->read->sectionCatalog();
        $meta = $catalog[$section];

        $this->write->resetSection($section, (int) $admin->id);

        return redirect()
            ->route('sdtg.content.edit', array_filter(['section' => $section, 'tab' => $request->input('tab')]))
            ->with('status', $meta['label'].' reset to defaults.');
    }

    /**
     * @param  array<string, mixed>  $slice
     * @return array<string, string|null>
     */
    private function resolveMediaUrls(string $section, ?string $tab, array $slice): array
    {
        $urls = [];
        foreach (array_keys($this->write->mediaFieldKinds($section, $tab)) as $field) {
            $path = trim((string) ($slice[$field] ?? ''));
            $urls[$field] = $path !== '' ? $this->assets->sdtgUrl($path) : null;
        }

        return $urls;
    }

    /**
     * @return array<string, UploadedFile|null>
     */
    private function collectUploads(Request $request, string $section, ?string $tab): array
    {
        $uploads = [];
        foreach (array_keys($this->write->mediaFieldKinds($section, $tab)) as $field) {
            $file = $request->file('media.'.$field);
            $uploads[$field] = $file instanceof UploadedFile ? $file : null;
        }

        return $uploads;
    }

    /**
     * @return array<string, bool>
     */
    private function collectRemovals(Request $request, string $section, ?string $tab): array
    {
        $removals = [];
        foreach (array_keys($this->write->mediaFieldKinds($section, $tab)) as $field) {
            $removals[$field] = $request->boolean('remove_media.'.$field);
        }

        return $removals;
    }

    private function ensureKnownSection(string $section): void
    {
        if (! $this->read->isKnownSection($section)) {
            throw new NotFoundHttpException('Unknown content section.');
        }
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
