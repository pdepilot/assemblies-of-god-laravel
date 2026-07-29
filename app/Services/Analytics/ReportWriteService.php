<?php

namespace App\Services\Analytics;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;

final class ReportWriteService
{
    use ReportPayloadBuilders;

    /**
     * Full Church Management System report catalog (slugs).
     *
     * @var list<string>
     */
    public const REPORT_TYPES = [
        'membership',
        'visitors',
        'attendance',
        'department',
        'ministries',
        'sunday_school',
        'admins',
        'events',
        'sermons',
        'donations',
        'recurring_giving',
        'partnerships',
        'financial',
        'newsletter',
        'contact_inbox',
        'testimonies',
        'communication',
        'registration_portals',
        'sdtg',
    ];

    public const FORMATS = ['csv', 'pdf', 'docx'];

    /**
     * Grouped options for the Reports Hub dropdown.
     *
     * @return list<array{group: string, options: list<array{value: string, label: string}}}>
     */
    public static function catalog(): array
    {
        return [
            [
                'group' => 'People & Membership',
                'options' => [
                    ['value' => 'membership', 'label' => 'Membership'],
                    ['value' => 'visitors', 'label' => 'Visitors'],
                    ['value' => 'attendance', 'label' => 'Attendance'],
                    ['value' => 'department', 'label' => 'Departments (Members by Department)'],
                    ['value' => 'ministries', 'label' => 'Ministry Rosters (Children, Youth, Choir, …)'],
                    ['value' => 'sunday_school', 'label' => 'Sunday School'],
                    ['value' => 'admins', 'label' => 'Administrators'],
                ],
            ],
            [
                'group' => 'Events & Teaching',
                'options' => [
                    ['value' => 'events', 'label' => 'Events'],
                    ['value' => 'sermons', 'label' => 'Sermons & Live'],
                ],
            ],
            [
                'group' => 'Giving & Finance',
                'options' => [
                    ['value' => 'donations', 'label' => 'Donations & Stewardship'],
                    ['value' => 'recurring_giving', 'label' => 'Recurring Giving'],
                    ['value' => 'partnerships', 'label' => 'Kingdom Partnership (Pledges)'],
                    ['value' => 'financial', 'label' => 'Financial ERP (Income & Expenses)'],
                ],
            ],
            [
                'group' => 'Communications & Website',
                'options' => [
                    ['value' => 'newsletter', 'label' => 'Newsletter Subscribers'],
                    ['value' => 'contact_inbox', 'label' => 'Contact Inbox'],
                    ['value' => 'testimonies', 'label' => 'Testimonies'],
                    ['value' => 'communication', 'label' => 'Communication Logs'],
                ],
            ],
            [
                'group' => 'Programs & Portals',
                'options' => [
                    ['value' => 'registration_portals', 'label' => 'Registration Portals'],
                    ['value' => 'sdtg', 'label' => 'SDTG Registrations'],
                ],
            ],
        ];
    }

    public function __construct(
        private readonly ReportReadService $reports,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generate(
        string $type,
        string $period,
        string $format,
        array $options,
        int $adminId,
        string $adminName,
    ): array {
        $type = strtolower(trim($type));

        if (! in_array($type, self::REPORT_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown report type.');
        }

        $format = $this->normalizeFormat($format);
        $range = $this->reports->resolvePeriod($type, $period, $options);
        $payload = $this->buildReportPayload($type, $range);
        $rowCount = (int) ($payload['row_count'] ?? 0);
        // Empty datasets are allowed so every CMS report type stays usable.
        $body = $this->renderPayload($payload, $format);
        $extension = $format === 'docx' ? 'docx' : $format;

        Storage::disk('local')->makeDirectory('reports');

        $safeSlug = preg_replace('/[^a-z0-9_-]+/i', '-', $type) ?: 'report';
        $fileName = sprintf(
            'ag-%s-%s-%s.%s',
            $safeSlug,
            date('Ymd-His'),
            substr(bin2hex(random_bytes(3)), 0, 6),
            $extension
        );

        $relativePath = 'reports/'.$fileName;
        Storage::disk('local')->put($relativePath, $body);
        $absolutePath = Storage::disk('local')->path($relativePath);
        $fileSize = (int) Storage::disk('local')->size($relativePath);

        $id = (int) DB::table('generated_reports')->insertGetId([
            'report_type' => $type,
            'title' => (string) ($payload['title'] ?? ucfirst($type).' Report'),
            'period_key' => $range['key'],
            'period_label' => $range['label'],
            'format' => $format,
            'file_path' => $absolutePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'row_count' => $rowCount,
            'generated_by' => $adminId > 0 ? $adminId : null,
            'generated_by_name' => $adminName,
            'download_count' => 0,
            'meta_json' => json_encode($payload['meta'] ?? [], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);

        $record = $this->reports->getReport($id);

        if (! $record) {
            throw new \RuntimeException('Report saved but could not be loaded.');
        }

        return $record;
    }

    public function delete(int $id): void
    {
        $record = $this->reports->getReport($id);

        if (! $record) {
            throw new \InvalidArgumentException('Report not found.');
        }

        $path = (string) ($record['file_path'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }

        // Also try storage-relative path if absolute path moved.
        $relative = 'reports/'.ltrim((string) ($record['file_name'] ?? ''), '/');
        if (Storage::disk('local')->exists($relative)) {
            Storage::disk('local')->delete($relative);
        }

        DB::table('generated_reports')->where('id', $id)->delete();
    }

    public function mimeForFormat(string $format): string
    {
        return match ($this->normalizeFormat($format)) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'text/csv; charset=utf-8',
        };
    }

    private function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));

        return match ($format) {
            'excel' => 'csv',
            'word', 'doc', 'docx' => 'docx',
            'pdf' => 'pdf',
            'csv' => 'csv',
            default => throw new \InvalidArgumentException('Unsupported export format.'),
        };
    }

    /**
     * @param  array<string, mixed>  $range
     * @return array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>, row_count: int, meta: array<string, mixed>}
     */
    private function buildReportPayload(string $type, array $range): array
    {
        return match ($type) {
            'membership' => $this->buildMembershipPayload($range),
            'events' => $this->buildEventsPayload($range),
            'financial' => $this->buildFinancialPayload($range),
            'sdtg' => $this->buildSdtgPayload($range),
            'visitors' => $this->buildVisitorsPayload($range),
            'attendance' => $this->buildAttendancePayload($range),
            'department' => $this->buildDepartmentPayload($range),
            'ministries' => $this->buildMinistriesPayload($range),
            'sunday_school' => $this->buildSundaySchoolPayload($range),
            'admins' => $this->buildAdminsPayload($range),
            'sermons' => $this->buildSermonsPayload($range),
            'donations' => $this->buildDonationsPayload($range),
            'recurring_giving' => $this->buildRecurringGivingPayload($range),
            'partnerships' => $this->buildPartnershipsPayload($range),
            'newsletter' => $this->buildNewsletterPayload($range),
            'contact_inbox' => $this->buildContactInboxPayload($range),
            'testimonies' => $this->buildTestimoniesPayload($range),
            'communication' => $this->buildCommunicationPayload($range),
            'registration_portals' => $this->buildRegistrationPortalsPayload($range),
            default => throw new \InvalidArgumentException('Unknown report type.'),
        };
    }

    /**
     * @param  array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>, row_count: int, meta: array<string, mixed>}  $payload
     */
    private function renderPayload(array $payload, string $format): string
    {
        return match ($format) {
            'pdf' => $this->pdfFromPayload($payload),
            'docx' => $this->docxFromPayload($payload),
            default => $this->csvFromRows($payload['headers'], $payload['rows']),
        };
    }

    /** @param  array<string, mixed>  $range */
    private function buildMembershipPayload(array $range): array
    {
        $rows = $this->fetchMembersForRange($range);

        return [
            'title' => 'Membership Report — '.$range['label'],
            'headers' => ['Member Code', 'Full Name', 'Email', 'Phone', 'Department', 'Status', 'Joined Date'],
            'rows' => array_map(static fn (array $row): array => [
                $row['member_code'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['department'] ?? '',
                $row['status'] ?? '',
                $row['joined_date'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'membership'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildEventsPayload(array $range): array
    {
        $rows = $this->fetchEventRows($range['start'] ?? null, $range['end'] ?? null);

        return [
            'title' => 'Events Report — '.$range['label'],
            'headers' => ['Code', 'Title', 'Category', 'Status', 'Event Date', 'Time', 'Location', 'Expected Attendance'],
            'rows' => array_map(static fn (array $row): array => [
                $row['event_code'] ?? '',
                $row['title'] ?? '',
                $row['category'] ?? '',
                $row['status'] ?? '',
                $row['event_date'] ?? '',
                $row['event_time'] ?? '',
                $row['location'] ?? '',
                $row['expected_attendance'] ?? 0,
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['events' => count($rows)],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildFinancialPayload(array $range): array
    {
        $start = $range['start'] ?? null;
        $end = $range['end'] ?? null;
        $incomeRows = $this->fetchErpIncomeRows($start, $end);
        $expenseRows = $this->fetchErpExpenseRows($start, $end);

        $rows = array_merge(
            array_map(static fn (array $row): array => [
                'Income',
                $row['income_date'] ?? '',
                $row['receipt_no'] ?? '',
                $row['category_name'] ?? '',
                number_format((float) $row['amount'], 2, '.', ''),
                $row['approval_status'] ?? '',
                $row['notes'] ?? '',
            ], $incomeRows),
            array_map(static fn (array $row): array => [
                'Expense',
                $row['expense_date'] ?? '',
                $row['voucher_no'] ?? '',
                $row['category_name'] ?? '',
                number_format((float) $row['amount'], 2, '.', ''),
                $row['approval_status'] ?? '',
                $row['notes'] ?? '',
            ], $expenseRows)
        );

        $totalIncome = array_sum(array_map(static fn (array $r): float => (float) $r['amount'], $incomeRows));
        $totalExpense = array_sum(array_map(static fn (array $r): float => (float) $r['amount'], $expenseRows));

        return [
            'title' => 'Financial Report — '.$range['label'],
            'headers' => ['Type', 'Date', 'Reference', 'Category', 'Amount', 'Status', 'Notes'],
            'rows' => $rows,
            'row_count' => count($rows),
            'meta' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net' => $totalIncome - $totalExpense,
            ],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildSdtgPayload(array $range): array
    {
        $year = (int) ($range['year'] ?? date('Y'));
        $registrations = $this->fetchSdtgRegistrations($year);

        return [
            'title' => 'SDTG '.$year.' Summary Report',
            'headers' => ['Section', 'Name', 'Email', 'Phone', 'Country', 'Ticket', 'Volunteer', 'Status', 'Date'],
            'rows' => array_map(static fn (array $row): array => [
                'Registration',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['country'] ?? '',
                $row['ticket_type'] ?? '',
                ! empty($row['is_volunteer']) ? 'Yes' : 'No',
                $row['status'] ?? '',
                $row['registration_date'] ?? '',
            ], $registrations),
            'row_count' => count($registrations),
            'meta' => ['year' => $year, 'registrations' => count($registrations)],
        ];
    }

    /**
     * @param  array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>, meta?: array<string, mixed>}  $payload
     */
    private function pdfFromPayload(array $payload): string
    {
        $title = e((string) $payload['title']);
        $headers = $payload['headers'];
        $rows = $payload['rows'];

        $thead = '';
        foreach ($headers as $header) {
            $thead .= '<th>'.e((string) $header).'</th>';
        }

        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $cell) {
                $tbody .= '<td>'.e((string) $cell).'</td>';
            }
            $tbody .= '</tr>';
        }

        if ($tbody === '') {
            $tbody = '<tr><td colspan="'.count($headers).'">No rows</td></tr>';
        }

        $metaHtml = '';
        foreach (($payload['meta'] ?? []) as $key => $value) {
            if (is_scalar($value)) {
                $metaHtml .= '<p><strong>'.e((string) $key).':</strong> '.e((string) $value).'</p>';
            }
        }

        $generatedAt = e($this->nowLabel());

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
h1 { font-size: 16px; margin: 0 0 8px; }
.meta { margin-bottom: 12px; color: #444; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
th { background: #f3f4f6; font-weight: bold; }
</style>
</head>
<body>
<h1>{$title}</h1>
<div class="meta">
<p>Generated: {$generatedAt}</p>
{$metaHtml}
</div>
<table>
<thead><tr>{$thead}</tr></thead>
<tbody>{$tbody}</tbody>
</table>
</body>
</html>
HTML;

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output() ?? '';
    }

    /**
     * @param  array{title: string, headers: list<string>, rows: list<list<string|int|float|null>>, meta?: array<string, mixed>}  $payload
     */
    private function docxFromPayload(array $payload): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $section->addText((string) $payload['title'], ['bold' => true, 'size' => 16]);
        $section->addText('Generated: '.$this->nowLabel());

        foreach (($payload['meta'] ?? []) as $key => $value) {
            if (is_scalar($value)) {
                $section->addText(ucfirst(str_replace('_', ' ', (string) $key)).': '.$value);
            }
        }

        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'unit' => TblWidth::PERCENT,
            'width' => 100 * 50,
        ]);

        $table->addRow();
        foreach ($payload['headers'] as $header) {
            $table->addCell(1500)->addText((string) $header, ['bold' => true]);
        }

        foreach ($payload['rows'] as $row) {
            $table->addRow();
            foreach ($row as $cell) {
                $table->addCell(1500)->addText((string) $cell);
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'agdocx');
        if ($temp === false) {
            throw new \RuntimeException('Unable to create temporary Word file.');
        }

        $docxPath = $temp.'.docx';
        @unlink($temp);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($docxPath);
        $binary = (string) file_get_contents($docxPath);
        @unlink($docxPath);

        return $binary;
    }

    private function nowLabel(): string
    {
        return now()->format('d M Y, g:i A');
    }

    /** @return list<array<string, mixed>> */
    private function fetchMembersForRange(array $range): array
    {
        if (! Schema::hasTable('members')) {
            return [];
        }

        $query = DB::table('members')->orderBy('full_name')->limit(10000);

        if (! empty($range['start'])) {
            $query->where('joined_date', '>=', $range['start']);
        }

        if (! empty($range['end'])) {
            $query->where('joined_date', '<=', $range['end']);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return list<array<string, mixed>> */
    private function fetchEventRows(?string $start, ?string $end): array
    {
        $query = DB::table('church_events')->orderBy('event_date')->orderByDesc('id')->limit(5000);

        if ($start) {
            $query->where('event_date', '>=', $start);
        }

        if ($end) {
            $query->where('event_date', '<=', $end);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return list<array<string, mixed>> */
    private function fetchSdtgRegistrations(int $year): array
    {
        return DB::table('sdtg_registrations')
            ->whereYear('registration_date', $year)
            ->orderByDesc('registration_date')
            ->orderByDesc('id')
            ->limit(10000)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function fetchErpIncomeRows(?string $start, ?string $end): array
    {
        $query = DB::table('erp_income as i')
            ->leftJoin('erp_income_categories as c', 'c.id', '=', 'i.category_id')
            ->whereNull('i.deleted_at')
            ->orderByDesc('i.income_date')
            ->limit(10000)
            ->select(['i.*', 'c.name as category_name']);

        if ($start) {
            $query->where('i.income_date', '>=', $start);
        }

        if ($end) {
            $query->where('i.income_date', '<=', $end);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return list<array<string, mixed>> */
    private function fetchErpExpenseRows(?string $start, ?string $end): array
    {
        $query = DB::table('erp_expenses as e')
            ->leftJoin('erp_expense_categories as c', 'c.id', '=', 'e.category_id')
            ->whereNull('e.deleted_at')
            ->orderByDesc('e.expense_date')
            ->limit(10000)
            ->select(['e.*', 'c.name as category_name']);

        if ($start) {
            $query->where('e.expense_date', '>=', $start);
        }

        if ($end) {
            $query->where('e.expense_date', '<=', $end);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string|null>>  $rows
     */
    private function csvFromRows(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV buffer.');
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}
