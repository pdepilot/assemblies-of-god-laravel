<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\BirthdayReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BirthdayController
{
    public function __construct(
        private readonly BirthdayReadService $read,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $days = max(1, min(366, (int) $request->query('days', 366)));

        return view('communication-hub.birthdays.index', [
            'board' => $this->read->board($days, 200),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function updateAutoEmail(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $enabled = $request->boolean('enabled');
        $this->read->setAutoEmailEnabled($enabled, (int) $admin->id);

        return redirect()
            ->route('communication-hub.birthdays.index', ['days' => (int) $request->input('days', 366)])
            ->with('status', 'Auto birthday email '.($enabled ? 'enabled' : 'disabled').'.');
    }

    public function updateAutoSms(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $enabled = $request->boolean('enabled');
        $this->read->setAutoSmsEnabled($enabled, (int) $admin->id);

        return redirect()
            ->route('communication-hub.birthdays.index', ['days' => (int) $request->input('days', 366)])
            ->with('status', 'Auto birthday SMS '.($enabled ? 'enabled' : 'disabled').'.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
