<?php

namespace App\Http\Controllers\SundaySchool;

use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\AnalyticsReadService;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\ReportReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnalyticsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly ReportReadService $reportRead,
        private readonly AnalyticsReadService $analytics,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewAnalytics($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $period = (string) $request->query('period', 'month');
        if (! in_array($period, ['month', 'quarter', 'year'], true)) {
            $period = 'month';
        }

        $tab = (string) $request->query('tab', 'overview');
        if (! in_array($tab, ['overview', 'intelligence'], true)) {
            $tab = 'overview';
        }

        $charts = $this->analytics->getChartData($scope);
        $superintendent = ($scope['can_view_correction_audit'] ?? false)
            ? $this->analytics->getSuperintendentDashboard($scope)
            : null;

        if ($superintendent !== null && $tab === 'intelligence') {
            $charts = $superintendent['charts'];
        }

        if ($tab === 'intelligence' && $superintendent === null) {
            $tab = 'overview';
        }

        return view('sunday-school.analytics.index', [
            'stats' => $this->reportRead->getDashboardStats($scope),
            'charts' => $charts,
            'teacherAnalytics' => $this->analytics->getTeacherAnalytics($scope, $period),
            'classAnalytics' => $this->analytics->getClassAnalytics($scope, $period),
            'superintendent' => $superintendent,
            'period' => $period,
            'tab' => $tab,
            'canIntelligence' => $superintendent !== null,
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }

    /**
     * @return array{id: int, role: string}
     */
    private function adminPayload(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'role' => (string) $admin->role,
        ];
    }
}
