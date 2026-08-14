<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReportReadService
{
    /** @return array<string, mixed> */
    public function getBootstrap(): array
    {
        return [
            'stats' => $this->getStats(),
            'filters' => $this->getFilterOptions(),
            'recent' => $this->listRecent(20),
        ];
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        if (! Schema::hasTable('generated_reports')) {
            return [
                'generated_ytd' => 0,
                'generated_month' => 0,
                'total_downloads' => 0,
                'scheduled_reports' => 0,
            ];
        }

        $yearStart = now()->startOfYear()->format('Y-m-d 00:00:00');
        $monthStart = now()->startOfMonth()->format('Y-m-d 00:00:00');

        return [
            'generated_ytd' => (int) DB::table('generated_reports')->where('created_at', '>=', $yearStart)->count(),
            'generated_month' => (int) DB::table('generated_reports')->where('created_at', '>=', $monthStart)->count(),
            'total_downloads' => (int) DB::table('generated_reports')->sum('download_count'),
            'scheduled_reports' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function getFilterOptions(): array
    {
        return [
            'membership' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'visitors' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'attendance' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'department' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'ministries' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'ministry_age_transfers' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'ministry_age_eligibility' => $this->periodOptions(['all_time']),
            'sunday_school' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'admins' => $this->periodOptions(['ytd', 'all_time']),
            'events' => $this->periodOptions(['next_30_days', 'last_quarter', 'ytd', 'all_time']),
            'sermons' => $this->periodOptions(['current_month', 'last_quarter', 'ytd', 'all_time']),
            'donations' => $this->periodOptions(['current_month', 'current_quarter', 'ytd', 'all_time']),
            'recurring_giving' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'partnerships' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'financial' => $this->periodOptions(['current_month', 'current_quarter', 'ytd', 'all_time']),
            'newsletter' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'contact_inbox' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'testimonies' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'communication' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'registration_portals' => $this->periodOptions(['current_month', 'ytd', 'all_time']),
            'formats' => collect(ReportWriteService::REPORT_TYPES)
                ->mapWithKeys(static fn (string $type): array => [$type => ['csv', 'pdf', 'docx']])
                ->all(),
            'catalog' => ReportWriteService::catalog(),
        ];
    }

    /**
     * @param  list<string>  $keys
     * @return list<array{key: string, label: string}>
     */
    private function periodOptions(array $keys): array
    {
        $options = [];

        foreach ($keys as $key) {
            $range = $this->resolvePeriod('generic', $key, []);
            $options[] = ['key' => $range['key'], 'label' => $range['label']];
        }

        return $options;
    }

    /** @return list<array<string, mixed>> */
    public function listRecent(int $limit = 20): array
    {
        if (! Schema::hasTable('generated_reports')) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        return DB::table('generated_reports as gr')
            ->leftJoin('admins as a', 'a.id', '=', 'gr.generated_by')
            ->orderByDesc('gr.created_at')
            ->orderByDesc('gr.id')
            ->limit($limit)
            ->get(['gr.*', 'a.full_name as admin_full_name'])
            ->map(fn ($row) => $this->formatReport((array) json_decode(json_encode($row), true)))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getReport(int $id): ?array
    {
        if (! Schema::hasTable('generated_reports')) {
            return null;
        }

        $row = DB::table('generated_reports as gr')
            ->leftJoin('admins as a', 'a.id', '=', 'gr.generated_by')
            ->where('gr.id', $id)
            ->first(['gr.*', 'a.full_name as admin_full_name']);

        return $row ? $this->formatReport((array) $row) : null;
    }

    public function incrementDownload(int $id): void
    {
        if (! Schema::hasTable('generated_reports')) {
            return;
        }

        DB::table('generated_reports')->where('id', $id)->increment('download_count');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{key: string, label: string, start: ?string, end: ?string, year?: int}
     */
    public function resolvePeriod(string $type, string $period, array $options): array
    {
        $period = trim($period);
        $today = new \DateTimeImmutable('today');

        return match ($period) {
            'current_month' => [
                'key' => 'current_month',
                'label' => $today->format('F Y'),
                'start' => $today->format('Y-m-01'),
                'end' => $today->format('Y-m-t'),
            ],
            'last_quarter' => $this->quarterRange($today, -1),
            'current_quarter' => $this->quarterRange($today, 0),
            'ytd' => [
                'key' => 'ytd',
                'label' => 'Year to Date '.$today->format('Y'),
                'start' => $today->format('Y-01-01'),
                'end' => $today->format('Y-m-d'),
            ],
            'all_time' => [
                'key' => 'all_time',
                'label' => 'All Time',
                'start' => null,
                'end' => null,
            ],
            'next_30_days' => [
                'key' => 'next_30_days',
                'label' => 'Next 30 Days',
                'start' => $today->format('Y-m-d'),
                'end' => $today->modify('+30 days')->format('Y-m-d'),
            ],
            default => throw new \InvalidArgumentException('Unknown report period.'),
        };
    }

    /** @return array{key: string, label: string, start: string, end: string} */
    private function quarterRange(\DateTimeImmutable $today, int $offset): array
    {
        $month = (int) $today->format('n');
        $currentQuarter = (int) ceil($month / 3);
        $targetQuarter = $currentQuarter + $offset;
        $year = (int) $today->format('Y');

        while ($targetQuarter < 1) {
            $targetQuarter += 4;
            $year--;
        }

        while ($targetQuarter > 4) {
            $targetQuarter -= 4;
            $year++;
        }

        $startMonth = (($targetQuarter - 1) * 3) + 1;
        $start = \DateTimeImmutable::createFromFormat('Y-n-j', "{$year}-{$startMonth}-1");

        if (! $start) {
            throw new \RuntimeException('Unable to resolve quarter range.');
        }

        $end = $start->modify('+2 months')->modify('last day of this month');

        return [
            'key' => $offset === 0 ? 'current_quarter' : 'last_quarter',
            'label' => 'Q'.$targetQuarter.' '.$year,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function formatReport(array $row): array
    {
        $meta = $row['meta_json'] ?? null;

        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }

        return [
            'id' => (int) $row['id'],
            'report_type' => (string) $row['report_type'],
            'title' => (string) $row['title'],
            'period_key' => (string) $row['period_key'],
            'period_label' => (string) $row['period_label'],
            'format' => (string) $row['format'],
            'file_path' => (string) $row['file_path'],
            'file_name' => (string) $row['file_name'],
            'file_size' => (int) $row['file_size'],
            'row_count' => (int) $row['row_count'],
            'generated_by' => isset($row['generated_by']) ? (int) $row['generated_by'] : null,
            'generated_by_name' => (string) ($row['generated_by_name'] ?? ''),
            'admin_full_name' => (string) ($row['admin_full_name'] ?? ''),
            'download_count' => (int) ($row['download_count'] ?? 0),
            'meta' => is_array($meta) ? $meta : [],
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}
