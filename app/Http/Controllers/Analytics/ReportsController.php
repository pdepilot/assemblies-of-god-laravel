<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Requests\Analytics\GenerateReportRequest;
use App\Models\Admin;
use App\Models\GeneratedReport;
use App\Policies\AnalyticsPolicy;
use App\Services\Analytics\ReportReadService;
use App\Services\Analytics\ReportWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportsController
{
    public function __construct(
        private readonly ReportReadService $reports,
        private readonly ReportWriteService $writer,
        private readonly AnalyticsPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewReports($admin);

        $bootstrap = $this->reports->getBootstrap();

        return view('analytics.reports.index', [
            'stats' => $bootstrap['stats'],
            'filters' => $bootstrap['filters'],
            'catalog' => $bootstrap['filters']['catalog'] ?? ReportWriteService::catalog(),
            'recent' => $bootstrap['recent'],
            'canExport' => $this->policy->exportReports($admin),
        ]);
    }

    public function generate(GenerateReportRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireExportReports($admin);

        $validated = $request->validated();

        try {
            $this->writer->generate(
                $validated['type'],
                $validated['period'],
                $validated['format'],
                [],
                (int) $admin->id,
                (string) ($admin->full_name ?: $admin->username),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['format' => $e->getMessage()]);
        }

        return redirect()
            ->route('analytics.reports.index')
            ->with('status', 'Report generated successfully.');
    }

    public function download(GeneratedReport $report): StreamedResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewReports($admin);

        $record = $this->reports->getReport((int) $report->id);

        if (! $record || ! is_file($record['file_path'])) {
            abort(404, 'Report file not found.');
        }

        $this->reports->incrementDownload((int) $report->id);

        return response()->streamDownload(function () use ($record): void {
            echo file_get_contents($record['file_path']);
        }, $record['file_name'], [
            'Content-Type' => $this->writer->mimeForFormat((string) $record['format']),
        ]);
    }

    public function destroy(GeneratedReport $report): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireExportReports($admin);

        try {
            $this->writer->delete((int) $report->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('analytics.reports.index')
            ->with('status', 'Report deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
