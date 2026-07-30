<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Controllers\Concerns\ResolvesSdtgAdmin;
use App\Http\Requests\Sdtg\SaveSdtgMediaAssetRequest;
use App\Http\Requests\Sdtg\SaveSdtgMediaFolderRequest;
use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgMediaReadService;
use App\Services\Sdtg\SdtgMediaWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class MediaLibraryController
{
    use ResolvesSdtgAdmin;

    public function __construct(
        private readonly SdtgMediaReadService $read,
        private readonly SdtgMediaWriteService $write,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        $filters = [
            'q' => (string) $request->query('q', ''),
            'folder_id' => (int) $request->query('folder_id', 0),
            'media_type' => (string) $request->query('media_type', ''),
            'year' => (int) $request->query('year', 0),
            'status' => (string) $request->query('status', ''),
        ];

        return view('sdtg.media-library.index', [
            'stats' => $this->read->stats(),
            'folders' => $this->read->listFolders(),
            'years' => $this->read->listYears(),
            'result' => $this->read->listAssets(
                $filters['q'],
                $filters['folder_id'],
                $filters['media_type'],
                $filters['year'],
                $filters['status'],
                max(1, (int) $request->query('page', 1)),
            ),
            'filters' => $filters,
            'mediaTypes' => SdtgMediaReadService::MEDIA_TYPES,
            'statuses' => SdtgMediaReadService::STATUSES,
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function createAsset(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        return view('sdtg.media-library.assets.create', [
            'asset' => [],
            'folders' => $this->read->listFolders(),
            'mediaTypes' => SdtgMediaReadService::MEDIA_TYPES,
            'statuses' => SdtgMediaReadService::STATUSES,
        ]);
    }

    public function storeAsset(SaveSdtgMediaAssetRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        try {
            $this->write->saveAsset(
                $request->validated(),
                $request->file('media'),
                $request->file('thumbnail'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['title' => $e->getMessage()])->withInput();
        }

        return redirect()->route('sdtg.media-library.index')->with('status', 'Media asset saved.');
    }

    public function editAsset(int $asset): View
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);
        $row = $this->read->getAsset($asset);
        abort_if($row === null, 404);

        return view('sdtg.media-library.assets.edit', [
            'asset' => $row,
            'folders' => $this->read->listFolders(),
            'mediaTypes' => SdtgMediaReadService::MEDIA_TYPES,
            'statuses' => SdtgMediaReadService::STATUSES,
        ]);
    }

    public function updateAsset(SaveSdtgMediaAssetRequest $request, int $asset): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        try {
            $this->write->saveAsset(
                $request->validated() + ['id' => $asset],
                $request->file('media'),
                $request->file('thumbnail'),
                $request->boolean('remove_media'),
                $request->boolean('remove_thumb'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['title' => $e->getMessage()])->withInput();
        }

        return redirect()->route('sdtg.media-library.index')->with('status', 'Media asset updated.');
    }

    public function destroyAsset(int $asset): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);
        $this->write->deleteAsset($asset);

        return redirect()->route('sdtg.media-library.index')->with('status', 'Media asset deleted.');
    }

    public function storeFolder(SaveSdtgMediaFolderRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        try {
            $this->write->saveFolder($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Folder saved.');
    }

    public function destroyFolder(int $folder): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        try {
            $this->write->deleteFolder($folder);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['name' => $e->getMessage()]);
        }

        return back()->with('status', 'Folder deleted.');
    }
}
