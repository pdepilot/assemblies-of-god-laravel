<?php

namespace App\Http\Controllers\Sdtg;

use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgLivestreamReadService;
use App\Services\Sdtg\SdtgLivestreamWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class LivestreamController
{
    public function __construct(
        private readonly SdtgLivestreamReadService $read,
        private readonly SdtgLivestreamWriteService $write,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.livestream.index', [
            'stats' => $this->read->getAdminStats(),
            'settings' => $this->read->getPlatformSettings(),
            'hasPageContent' => $this->read->hasSavedPageContent(),
            'canManage' => $this->policy->manageSdtg($admin),
            'previewUrl' => route('public.sdtg.page', ['path' => 'livestream']),
        ]);
    }

    public function toggleLive(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        $validated = $request->validate([
            'is_live' => ['required', 'boolean'],
            'current_viewers' => ['nullable', 'integer', 'min:0'],
            'session_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->write->setLiveState(
                (bool) $validated['is_live'],
                (int) ($validated['current_viewers'] ?? 0),
                (string) ($validated['session_name'] ?? 'SDTG Livestream'),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['livestream' => $e->getMessage()]);
        }

        $message = $validated['is_live'] ? 'Stream is now live.' : 'Stream ended.';

        return redirect()->route('sdtg.livestream.index')->with('status', $message);
    }

    public function updateViewers(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        $validated = $request->validate([
            'current_viewers' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $this->write->updateViewers((int) $validated['current_viewers'], (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['current_viewers' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.livestream.index')->with('status', 'Viewer count updated.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSdtg($admin);

        try {
            $this->write->savePlatformSettings($request->all(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['settings' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.livestream.index')->with('status', 'Stream platform settings saved.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
