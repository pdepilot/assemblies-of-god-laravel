<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\HubDashboardReadService;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly HubDashboardReadService $dashboard,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.dashboard', [
            'stats' => $this->dashboard->getStats((int) $admin->id),
            'recent' => $this->dashboard->recentLogs(12),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
