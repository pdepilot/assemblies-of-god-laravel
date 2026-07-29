<?php

namespace App\Http\Controllers\Ministries;

use App\Http\Requests\Ministries\UpdateMinistrySettingRequest;
use App\Models\Admin;
use App\Models\MinistrySetting;
use App\Policies\MinistryPolicy;
use App\Services\Ministries\MinistryModuleReadService;
use App\Services\Ministries\MinistrySettingsReadService;
use App\Services\Ministries\MinistrySettingsWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class MinistrySettingsController
{
    public function __construct(
        private readonly MinistrySettingsReadService $read,
        private readonly MinistrySettingsWriteService $write,
        private readonly MinistryModuleReadService $moduleRead,
        private readonly MinistryPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewMinistries($admin);

        $settings = $this->read->all();
        $statsByKey = [];
        foreach ($settings as $setting) {
            $statsByKey[$setting['ministry_key']] = $this->moduleRead->getStats($setting['ministry_key']);
        }

        return view('ministries.settings.index', [
            'settings' => $settings,
            'statsByKey' => $statsByKey,
            'canManage' => $this->policy->manageSettings($admin),
        ]);
    }

    public function update(UpdateMinistrySettingRequest $request, MinistrySetting $setting): RedirectResponse
    {
        $this->policy->requireManageSettings($this->admin());

        try {
            $this->write->update((int) $setting->id, $request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('ministries.settings.index')->with('status', 'Ministry setting updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
