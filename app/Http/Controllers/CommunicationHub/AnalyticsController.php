<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\AnalyticsReadService;
use Illuminate\View\View;

final class AnalyticsController
{
    public function __construct(
        private readonly AnalyticsReadService $read,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $analytics = $this->read->getAnalytics();

        return view('communication-hub.analytics.index', [
            'kpis' => $analytics['kpis'],
            'monthlyTrends' => $analytics['monthly_trends'],
            'topTemplates' => $analytics['top_templates'],
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
