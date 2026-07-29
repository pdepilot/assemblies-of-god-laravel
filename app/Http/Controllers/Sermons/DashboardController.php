<?php

namespace App\Http\Controllers\Sermons;

use App\Models\Admin;
use App\Policies\SermonPolicy;
use App\Services\Sermons\SermonDashboardReadService;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly SermonDashboardReadService $dashboard,
        private readonly SermonPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSermons($admin);

        return view('sermons.dashboard', [
            'stats' => $this->dashboard->getStats(),
            'canManage' => $this->policy->manageSermons($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
