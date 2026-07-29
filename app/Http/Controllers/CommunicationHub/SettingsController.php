<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveHubSettingsRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\HubSettingsReadService;
use App\Services\CommunicationHub\HubSettingsWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsController
{
    public function __construct(
        private readonly HubSettingsReadService $read,
        private readonly HubSettingsWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function edit(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        return view('communication-hub.settings.edit', [
            'settings' => $this->read->getSettings(),
            'canManage' => true,
        ]);
    }

    public function update(SaveHubSettingsRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $this->write->save($request->validated(), (int) $admin->id);

        return redirect()->route('communication-hub.settings.edit')->with('status', 'Settings saved.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
