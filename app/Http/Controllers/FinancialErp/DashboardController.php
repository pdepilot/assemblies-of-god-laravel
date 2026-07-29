<?php

namespace App\Http\Controllers\FinancialErp;

use App\Models\Admin;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\ErpDashboardReadService;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly ErpDashboardReadService $dashboard,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);

        return view('financial-erp.dashboard', [
            'stats' => $this->dashboard->getStats(),
            'settings' => $this->dashboard->getSettings(),
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
