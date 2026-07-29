<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Requests\Sdtg\SaveSdtgGalleryAlbumRequest;
use App\Http\Requests\Sdtg\SaveSdtgGalleryItemRequest;
use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgCommunityReadService;
use App\Services\Sdtg\SdtgCommunityWriteService;
use App\Services\Sdtg\SdtgEditionsReadService;
use App\Services\Sdtg\SdtgEditionsWriteService;
use App\Services\Sdtg\SdtgGalleryReadService;
use App\Services\Sdtg\SdtgGalleryWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class GalleryController
{
    public function __construct(
        private readonly SdtgGalleryReadService $read,
        private readonly SdtgGalleryWriteService $write,
        private readonly SdtgEditionsReadService $editionsRead,
        private readonly SdtgEditionsWriteService $editionsWrite,
        private readonly SdtgCommunityReadService $communityRead,
        private readonly SdtgCommunityWriteService $communityWrite,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        $year = (int) $request->query('year', 0);
        $albumId = (int) $request->query('album_id', 0);
        $tab = (string) $request->query('tab', 'photos');
        $allowedTabs = ['photos', 'videos', 'featured', 'collections', 'timeline', 'community', 'social', 'share'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'photos';
        }

        $mediaType = match ($tab) {
            'photos' => 'photo',
            'videos' => 'video',
            default => (string) $request->query('media_type', ''),
        };
        $featuredOnly = $tab === 'featured';

        return view('sdtg.gallery.index', [
            'stats' => $this->read->stats(),
            'albums' => $this->read->listAlbums($year > 0 ? $year : null),
            'result' => $this->read->listItems(
                (string) $request->query('q', ''),
                $albumId,
                (string) $request->query('category', ''),
                $year,
                $mediaType,
                max(1, (int) $request->query('page', 1)),
                24,
                $featuredOnly,
            ),
            'editions' => $this->editionsRead->listEditions(),
            'memories' => $this->communityRead->listMemorySubmissions(
                $tab === 'share' ? 'pending' : (string) $request->query('memory_status', '')
            ),
            'categories' => SdtgGalleryReadService::CATEGORIES,
            'tab' => $tab,
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'year' => $year,
                'album_id' => $albumId,
                'category' => (string) $request->query('category', ''),
                'media_type' => $mediaType,
                'memory_status' => (string) $request->query('memory_status', $tab === 'share' ? 'pending' : ''),
            ],
            'previewUrl' => route('public.sdtg.page', ['path' => 'gallery']),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function createAlbum(): View
    {
        $this->policy->requireManageSdtg($this->admin());

        return view('sdtg.gallery.albums.create', ['album' => []]);
    }

    public function storeAlbum(SaveSdtgGalleryAlbumRequest $request): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $this->write->saveAlbum(
                $request->validated() + ['is_published' => $request->boolean('is_published')],
                $request->file('cover'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.gallery.index', ['tab' => 'collections'])->with('status', 'Album saved.');
    }

    public function editAlbum(int $album): View
    {
        $this->policy->requireManageSdtg($this->admin());
        $row = $this->read->getAlbum($album);
        abort_if($row === null, 404);

        return view('sdtg.gallery.albums.edit', ['album' => $row]);
    }

    public function updateAlbum(SaveSdtgGalleryAlbumRequest $request, int $album): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $this->write->saveAlbum(
                $request->validated() + [
                    'id' => $album,
                    'is_published' => $request->boolean('is_published'),
                ],
                $request->file('cover'),
                $request->boolean('remove_cover'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.gallery.index', ['tab' => 'collections'])->with('status', 'Album updated.');
    }

    public function destroyAlbum(int $album): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->write->deleteAlbum($album);

        return redirect()->route('sdtg.gallery.index', ['tab' => 'collections'])->with('status', 'Album deleted.');
    }

    public function createItem(Request $request): View
    {
        $this->policy->requireManageSdtg($this->admin());

        $type = (string) $request->query('type', 'photo');
        if (! in_array($type, ['photo', 'video'], true)) {
            $type = 'photo';
        }

        return view('sdtg.gallery.items.create', [
            'item' => [
                'media_type' => $type,
                'is_featured' => $request->boolean('featured'),
                'category' => $type === 'video' ? 'videos' : 'highlights',
            ],
            'section' => $type === 'video' ? 'videos' : ($request->boolean('featured') ? 'featured' : 'photos'),
            'albums' => $this->read->listAlbums(),
            'categories' => SdtgGalleryReadService::CATEGORIES,
            'layouts' => SdtgGalleryReadService::LAYOUTS,
        ]);
    }

    public function storeItem(SaveSdtgGalleryItemRequest $request): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $saved = $this->write->saveItem(
                $request->validated() + [
                    'is_published' => $request->boolean('is_published'),
                    'is_featured' => $request->boolean('is_featured'),
                    'is_speakers_highlight' => $request->boolean('is_speakers_highlight'),
                ],
                $request->file('media'),
                $request->file('thumbnail'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()
            ->route('sdtg.gallery.index', ['tab' => $this->tabForItem($saved)])
            ->with('status', 'Gallery item saved.');
    }

    public function editItem(int $item): View
    {
        $this->policy->requireManageSdtg($this->admin());
        $row = $this->read->getItem($item);
        abort_if($row === null, 404);

        return view('sdtg.gallery.items.edit', [
            'item' => $row,
            'section' => $this->tabForItem($row),
            'albums' => $this->read->listAlbums(),
            'categories' => SdtgGalleryReadService::CATEGORIES,
            'layouts' => SdtgGalleryReadService::LAYOUTS,
        ]);
    }

    public function updateItem(SaveSdtgGalleryItemRequest $request, int $item): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $saved = $this->write->saveItem(
                $request->validated() + [
                    'id' => $item,
                    'is_published' => $request->boolean('is_published'),
                    'is_featured' => $request->boolean('is_featured'),
                    'is_speakers_highlight' => $request->boolean('is_speakers_highlight'),
                ],
                $request->file('media'),
                $request->file('thumbnail'),
                $request->boolean('remove_media'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()
            ->route('sdtg.gallery.index', ['tab' => $this->tabForItem($saved)])
            ->with('status', 'Gallery item updated.');
    }

    public function destroyItem(int $item): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $row = $this->read->getItem($item);
        $tab = $row ? $this->tabForItem($row) : 'photos';
        $this->write->deleteItem($item);

        return redirect()->route('sdtg.gallery.index', ['tab' => $tab])->with('status', 'Gallery item deleted.');
    }

    public function storeEdition(Request $request): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $data = $request->validate([
            'crusade_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'theme' => ['required', 'string', 'max:255'],
            'speakers_summary' => ['required', 'string'],
            'highlights' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_start_at' => ['nullable', 'string'],
            'event_end_at' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable'],
            'is_next_crusade' => ['nullable'],
        ]);

        try {
            $this->editionsWrite->save($data + [
                'is_published' => $request->boolean('is_published', true),
                'is_next_crusade' => $request->boolean('is_next_crusade'),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['theme' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.gallery.index', ['tab' => 'timeline'])->with('status', 'Crusade edition saved. It now appears on the public gallery timeline.');
    }

    public function updateEdition(Request $request, int $edition): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $data = $request->validate([
            'crusade_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'theme' => ['required', 'string', 'max:255'],
            'speakers_summary' => ['required', 'string'],
            'highlights' => ['nullable', 'string'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_start_at' => ['nullable', 'string'],
            'event_end_at' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable'],
            'is_next_crusade' => ['nullable'],
        ]);

        try {
            $this->editionsWrite->save($data + [
                'id' => $edition,
                'is_published' => $request->boolean('is_published'),
                'is_next_crusade' => $request->boolean('is_next_crusade'),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['theme' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.gallery.index', ['tab' => 'timeline'])->with('status', 'Edition updated.');
    }

    public function destroyEdition(int $edition): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->editionsWrite->delete($edition);

        return redirect()->route('sdtg.gallery.index', ['tab' => 'timeline'])->with('status', 'Edition deleted.');
    }

    public function updateMemory(Request $request, int $memory): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $this->communityWrite->updateMemoryStatus($memory, (string) $request->input('status', 'pending'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        $tab = (string) $request->input('return_tab', 'community');
        if (! in_array($tab, ['community', 'share'], true)) {
            $tab = 'community';
        }

        return redirect()->route('sdtg.gallery.index', ['tab' => $tab])->with('status', 'Community memory updated for the public gallery.');
    }

    /** @param  array<string, mixed>  $item */
    private function tabForItem(array $item): string
    {
        if (($item['media_type'] ?? '') === 'video') {
            return 'videos';
        }
        if (! empty($item['is_featured'])) {
            return 'featured';
        }

        return 'photos';
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
