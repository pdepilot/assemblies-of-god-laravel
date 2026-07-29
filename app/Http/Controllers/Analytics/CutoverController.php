<?php

namespace App\Http\Controllers\Analytics;

use App\Models\Admin;
use App\Policies\AnalyticsPolicy;
use App\Services\Analytics\CutoverReadService;
use Illuminate\View\View;

final class CutoverController
{
    public function __construct(
        private readonly CutoverReadService $cutover,
        private readonly AnalyticsPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewTraffic($admin);

        return view('analytics.cutover.index', [
            'status' => $this->cutover->getStatus(),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
