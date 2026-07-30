<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Controllers\Concerns\ResolvesSdtgAdmin;
use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgDashboardReadService;
use Illuminate\View\View;

final class DashboardController
{
    use ResolvesSdtgAdmin;

    public function __construct(
        private readonly SdtgDashboardReadService $dashboard,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.dashboard', [
            'stats' => $this->dashboard->getStats(),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }
}
