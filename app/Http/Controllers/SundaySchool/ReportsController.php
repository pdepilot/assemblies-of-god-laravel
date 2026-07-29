<?php

namespace App\Http\Controllers\SundaySchool;

use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\ReportReadService;
use App\Services\SundaySchool\ReportWriteService;
use App\Services\SundaySchool\StudentReadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $studentRead,
        private readonly ReportReadService $read,
        private readonly ReportWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewReports($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $classId = (int) $request->query('class_id', 0);
        $reportDate = (string) $request->query('report_date', now()->toDateString());
        $generate = $request->boolean('generate');

        $classReport = null;
        $classReportError = null;

        if ($generate && $classId > 0) {
            try {
                $classReport = $this->read->generateClassReport($classId, $reportDate, $scope, (int) $admin->id);
            } catch (InvalidArgumentException $e) {
                $classReportError = $e->getMessage();
            }
        }

        return view('sunday-school.reports.index', [
            'stats' => $this->read->getDashboardStats($scope),
            'classes' => $this->studentRead->activeClassOptions($scope),
            'classId' => $classId,
            'from' => $from,
            'to' => $to,
            'reportDate' => $reportDate,
            'classReport' => $classReport,
            'classReportError' => $classReportError,
            'punctuality' => $this->read->punctualityReport($scope, $from, $to),
            'rankings' => $this->read->studentRankings($scope, 20),
            'canExportTeachers' => $this->policy->exportTeachers($admin),
        ]);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $admin = $this->admin();
        $this->policy->requireViewReports($admin);

        $type = (string) $request->query('type', 'students');
        if ($type === 'teachers') {
            $this->policy->requireExportTeachers($admin);
        }

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $classId = (int) $request->query('class_id', 0);
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        try {
            $export = $this->write->buildExport($type, $scope, $classId, $from, $to);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        return response()->streamDownload(
            static function () use ($export): void {
                echo $export['content'];
            },
            $export['filename'],
            ['Content-Type' => 'text/csv; charset=utf-8'],
        );
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
