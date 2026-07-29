<?php

namespace App\Http\Controllers\Security;

use App\Models\Admin;
use App\Policies\SecurityPolicy;
use App\Services\Security\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActivityLogsController
{
    public function __construct(
        private readonly ActivityLogService $logs,
        private readonly SecurityPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $this->policy->requireViewSecurity($this->admin());

        $defaults = $this->logs->defaultDates();
        $filters = $this->filtersFromRequest($request, $defaults);
        $result = $this->logs->list($filters);
        $stats = $this->logs->stats($filters);

        return view('security.activity-logs.index', [
            'filters' => $filters,
            'result' => $result,
            'stats' => $stats,
            'modules' => $this->logs->modules(),
            'admins' => $this->logs->admins(),
        ]);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $this->policy->requireViewSecurity($this->admin());

        $defaults = $this->logs->defaultDates();
        $filters = $this->filtersFromRequest($request, $defaults);
        $csv = "\xEF\xBB\xBF".$this->logs->exportCsv($filters);
        $filename = 'activity-logs-'.now()->format('Ymd-His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param  array{date_from: string, date_to: string}  $defaults
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request, array $defaults): array
    {
        $module = trim((string) $request->query('module', 'all'));
        $moduleIds = array_column($this->logs->modules(), 'id');
        if (! in_array($module, $moduleIds, true)) {
            $module = 'all';
        }

        $severity = trim((string) $request->query('severity', ''));
        if (! in_array($severity, ['', 'info', 'warning', 'critical'], true)) {
            $severity = '';
        }

        return [
            'date_from' => trim((string) $request->query('date_from', $defaults['date_from'])),
            'date_to' => trim((string) $request->query('date_to', $defaults['date_to'])),
            'module' => $module,
            'admin_id' => max(0, (int) $request->query('admin_id', 0)),
            'severity' => $severity,
            'q' => trim((string) $request->query('q', '')),
            'page' => max(1, (int) $request->query('page', 1)),
            'per_page' => max(1, min(100, (int) $request->query('per_page', 20))),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
