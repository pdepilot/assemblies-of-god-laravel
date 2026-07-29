<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payload builders for full Church Management System report types.
 */
trait ReportPayloadBuilders
{
    /** @param  array<string, mixed>  $range */
    private function buildVisitorsPayload(array $range): array
    {
        $rows = $this->tableRows('visitors', 'first_visit_date', $range, ['full_name']);

        return [
            'title' => 'Visitors Report — '.$range['label'],
            'headers' => ['Code', 'Full Name', 'Email', 'Phone', 'First Visit', 'Last Visit', 'Visits', 'Follow-up', 'Department Interest'],
            'rows' => array_map(static fn (array $row): array => [
                $row['visitor_code'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['first_visit_date'] ?? '',
                $row['last_visit_date'] ?? '',
                $row['visit_count'] ?? 0,
                $row['follow_up_status'] ?? '',
                $row['interested_department'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'visitors'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildAttendancePayload(array $range): array
    {
        if (Schema::hasTable('attendance_records')) {
            $rows = $this->tableRows('attendance_records', 'service_date', $range, ['service_date']);

            return [
                'title' => 'Attendance Report — '.$range['label'],
                'headers' => ['Date', 'Service', 'Department', 'Present', 'Absent', 'Notes'],
                'rows' => array_map(static fn (array $row): array => [
                    $row['service_date'] ?? '',
                    $row['service_type'] ?? ($row['service_name'] ?? ''),
                    $row['department'] ?? '',
                    $row['present_count'] ?? ($row['attendance_count'] ?? ''),
                    $row['absent_count'] ?? '',
                    $row['notes'] ?? '',
                ], $rows),
                'row_count' => count($rows),
                'meta' => ['type' => 'attendance', 'source' => 'attendance_records'],
            ];
        }

        $rows = [];
        if (Schema::hasTable('sunday_school_attendance')) {
            $query = DB::table('sunday_school_attendance as a')
                ->leftJoin('sunday_school_students as s', 's.id', '=', 'a.student_id')
                ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'a.class_id')
                ->orderByDesc('a.attendance_date')
                ->limit(10000)
                ->select(['a.attendance_date', 'a.status', 's.full_name', 'c.class_name']);
            if (! empty($range['start'])) {
                $query->where('a.attendance_date', '>=', $range['start']);
            }
            if (! empty($range['end'])) {
                $query->where('a.attendance_date', '<=', $range['end']);
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Attendance Report — '.$range['label'],
            'headers' => ['Date', 'Class', 'Student', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['attendance_date'] ?? '',
                $row['class_name'] ?? '',
                $row['full_name'] ?? '',
                $row['status'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'attendance', 'source' => 'sunday_school_attendance'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildDepartmentPayload(array $range): array
    {
        $rows = $this->fetchMembersForRange($range);

        return [
            'title' => 'Departments Report — '.$range['label'],
            'headers' => ['Department', 'Member Code', 'Full Name', 'Email', 'Phone', 'Status', 'Joined Date'],
            'rows' => array_map(static fn (array $row): array => [
                $row['department'] ?: 'Unassigned',
                $row['member_code'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['status'] ?? '',
                $row['joined_date'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'department'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildMinistriesPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('ministry_roster_people')) {
            $query = DB::table('ministry_roster_people')->orderBy('ministry_key')->orderBy('full_name')->limit(10000);
            if (! empty($range['start']) && Schema::hasColumn('ministry_roster_people', 'joined_date')) {
                $query->where(function ($q) use ($range): void {
                    $q->whereNull('joined_date')->orWhere('joined_date', '>=', $range['start']);
                });
            }
            if (! empty($range['end']) && Schema::hasColumn('ministry_roster_people', 'joined_date')) {
                $query->where(function ($q) use ($range): void {
                    $q->whereNull('joined_date')->orWhere('joined_date', '<=', $range['end']);
                });
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Ministry Rosters — '.$range['label'],
            'headers' => ['Ministry', 'Code', 'Full Name', 'Phone', 'Email', 'Group', 'Role', 'Status', 'Joined'],
            'rows' => array_map(static fn (array $row): array => [
                $row['ministry_key'] ?? '',
                $row['person_code'] ?? '',
                $row['full_name'] ?? '',
                $row['phone'] ?? '',
                $row['email'] ?? '',
                $row['group_name'] ?? '',
                $row['role_note'] ?? '',
                $row['status'] ?? '',
                $row['joined_date'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'ministries'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildSundaySchoolPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('sunday_school_students')) {
            $query = DB::table('sunday_school_students as s')
                ->leftJoin('sunday_school_classes as c', 'c.id', '=', 's.class_id')
                ->orderBy('c.class_name')
                ->orderBy('s.full_name')
                ->limit(10000)
                ->select(['s.*', 'c.class_name']);
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Sunday School Report — '.$range['label'],
            'headers' => ['Class', 'Student', 'Gender', 'Age Group', 'Phone', 'Parent', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['class_name'] ?? '',
                $row['full_name'] ?? '',
                $row['gender'] ?? '',
                $row['age_group'] ?? ($row['age'] ?? ''),
                $row['phone'] ?? ($row['parent_phone'] ?? ''),
                $row['parent_name'] ?? '',
                $row['status'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'sunday_school'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildAdminsPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('admins')) {
            $query = DB::table('admins')->orderBy('full_name')->limit(5000);
            if (! empty($range['start']) && Schema::hasColumn('admins', 'created_at')) {
                $query->where('created_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end']) && Schema::hasColumn('admins', 'created_at')) {
                $query->where('created_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Administrators Report — '.$range['label'],
            'headers' => ['Name', 'Email', 'Username', 'Role', 'Department', 'Status', 'Created'],
            'rows' => array_map(static fn (array $row): array => [
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['username'] ?? '',
                $row['role'] ?? '',
                $row['department'] ?? '',
                $row['account_status'] ?? ((int) ($row['is_active'] ?? 0) === 1 ? 'active' : 'inactive'),
                $row['created_at'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'admins'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildSermonsPayload(array $range): array
    {
        $rows = $this->tableRows('sermons', 'sermon_date', $range, ['sermon_date', 'title']);

        return [
            'title' => 'Sermons Report — '.$range['label'],
            'headers' => ['Code', 'Title', 'Minister', 'Date', 'Type', 'Status', 'Featured'],
            'rows' => array_map(static fn (array $row): array => [
                $row['sermon_code'] ?? '',
                $row['title'] ?? '',
                $row['minister_name'] ?? '',
                $row['sermon_date'] ?? '',
                $row['sermon_type'] ?? '',
                $row['status'] ?? '',
                ! empty($row['is_featured']) ? 'Yes' : 'No',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'sermons'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildDonationsPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('donations')) {
            $dateCol = Schema::hasColumn('donations', 'donated_at') ? 'donated_at'
                : (Schema::hasColumn('donations', 'donation_date') ? 'donation_date' : 'created_at');
            $query = DB::table('donations')->orderByDesc($dateCol)->limit(10000);
            if (! empty($range['start'])) {
                $start = $range['start'];
                $query->where($dateCol, '>=', strlen($start) === 10 ? $start.' 00:00:00' : $start);
            }
            if (! empty($range['end'])) {
                $end = $range['end'];
                $query->where($dateCol, '<=', strlen($end) === 10 ? $end.' 23:59:59' : $end);
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Donations Report — '.$range['label'],
            'headers' => ['Code', 'Donor', 'Email', 'Amount', 'Currency', 'Category', 'Anonymous', 'Created'],
            'rows' => array_map(static fn (array $row): array => [
                $row['donation_code'] ?? '',
                $row['donor_name'] ?? '',
                $row['donor_email'] ?? '',
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                $row['currency'] ?? 'NGN',
                $row['category'] ?? '',
                ! empty($row['is_anonymous']) ? 'Yes' : 'No',
                $row['donated_at'] ?? ($row['donation_date'] ?? ($row['created_at'] ?? '')),
            ], $rows),
            'row_count' => count($rows),
            'meta' => [
                'type' => 'donations',
                'total_amount' => array_sum(array_map(static fn (array $r): float => (float) ($r['amount'] ?? 0), $rows)),
            ],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildRecurringGivingPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('commitment_givers')) {
            $query = DB::table('commitment_givers as g')
                ->leftJoin('commitment_programs as p', 'p.id', '=', 'g.program_id')
                ->orderBy('g.donor_name')
                ->limit(10000)
                ->select(['g.*', 'p.name as program_name']);
            if (! empty($range['start']) && Schema::hasColumn('commitment_givers', 'created_at')) {
                $query->where('g.created_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end']) && Schema::hasColumn('commitment_givers', 'created_at')) {
                $query->where('g.created_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Recurring Giving Report — '.$range['label'],
            'headers' => ['Donor', 'Email', 'Phone', 'Program', 'Committed', 'Paid', 'Frequency', 'Status'],
            'rows' => array_map(static fn (array $row): array => [
                $row['donor_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['program_name'] ?? '',
                number_format((float) ($row['committed_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['amount_paid'] ?? 0), 2, '.', ''),
                $row['frequency'] ?? '',
                $row['status'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'recurring_giving'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildPartnershipsPayload(array $range): array
    {
        $rows = $this->tableRows('pledges', 'start_date', $range, ['donor_name']);

        return [
            'title' => 'Kingdom Partnership Report — '.$range['label'],
            'headers' => ['Donor', 'Email', 'Phone', 'Pledged', 'Paid', 'Remaining', 'Status', 'Start', 'End'],
            'rows' => array_map(static fn (array $row): array => [
                $row['donor_name'] ?? '',
                $row['donor_email'] ?? '',
                $row['donor_phone'] ?? '',
                number_format((float) ($row['pledged_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['amount_paid'] ?? 0), 2, '.', ''),
                number_format((float) ($row['remaining_balance'] ?? 0), 2, '.', ''),
                $row['status'] ?? '',
                $row['start_date'] ?? '',
                $row['end_date'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'partnerships'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildNewsletterPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('site_newsletter_subscribers')) {
            $query = DB::table('site_newsletter_subscribers')->orderByDesc('subscribed_at')->limit(10000);
            if (! empty($range['start'])) {
                $query->where('subscribed_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end'])) {
                $query->where('subscribed_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Newsletter Subscribers — '.$range['label'],
            'headers' => ['Email', 'Source', 'Status', 'Subscribed', 'Unsubscribed'],
            'rows' => array_map(static fn (array $row): array => [
                $row['email'] ?? '',
                $row['source'] ?? '',
                $row['status'] ?? '',
                $row['subscribed_at'] ?? '',
                $row['unsubscribed_at'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'newsletter'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildContactInboxPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('contact_submissions')) {
            $query = DB::table('contact_submissions')->orderByDesc('created_at')->limit(10000);
            if (! empty($range['start'])) {
                $query->where('created_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end'])) {
                $query->where('created_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Contact Inbox Report — '.$range['label'],
            'headers' => ['Code', 'Type', 'Name', 'Email', 'Subject', 'Status', 'Created'],
            'rows' => array_map(static fn (array $row): array => [
                $row['submission_code'] ?? '',
                $row['inquiry_type'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['subject'] ?? '',
                $row['status'] ?? '',
                $row['created_at'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'contact_inbox'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildTestimoniesPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('site_testimonies')) {
            $query = DB::table('site_testimonies')->orderByDesc('created_at')->limit(5000);
            if (! empty($range['start'])) {
                $query->where('created_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end'])) {
                $query->where('created_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Testimonies Report — '.$range['label'],
            'headers' => ['Name', 'Email', 'Role', 'Category', 'Status', 'Featured', 'Created'],
            'rows' => array_map(static fn (array $row): array => [
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['role_title'] ?? '',
                $row['category_slug'] ?? '',
                $row['status'] ?? '',
                ! empty($row['is_featured']) ? 'Yes' : 'No',
                $row['created_at'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'testimonies'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildCommunicationPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('communication_logs')) {
            $query = DB::table('communication_logs')->orderByDesc('id')->limit(10000);
            if (Schema::hasColumn('communication_logs', 'created_at')) {
                if (! empty($range['start'])) {
                    $query->where('created_at', '>=', $range['start'].' 00:00:00');
                }
                if (! empty($range['end'])) {
                    $query->where('created_at', '<=', $range['end'].' 23:59:59');
                }
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Communication Logs — '.$range['label'],
            'headers' => ['Channel', 'Subject', 'Recipient', 'Status', 'Opened', 'Clicked', 'Error'],
            'rows' => array_map(static fn (array $row): array => [
                $row['channel'] ?? '',
                $row['subject'] ?? '',
                $row['recipient'] ?? ($row['recipient_name'] ?? ''),
                $row['status'] ?? '',
                ! empty($row['opened']) ? 'Yes' : 'No',
                ! empty($row['clicked']) ? 'Yes' : 'No',
                $row['error_message'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'communication'],
        ];
    }

    /** @param  array<string, mixed>  $range */
    private function buildRegistrationPortalsPayload(array $range): array
    {
        $rows = [];
        if (Schema::hasTable('registrants')) {
            $query = DB::table('registrants as r')
                ->leftJoin('registration_portals as p', 'p.id', '=', 'r.portal_id')
                ->orderByDesc('r.id')
                ->limit(10000)
                ->select(['r.*', 'p.title as portal_title', 'p.slug as portal_slug']);
            if (! empty($range['start']) && Schema::hasColumn('registrants', 'created_at')) {
                $query->where('r.created_at', '>=', $range['start'].' 00:00:00');
            }
            if (! empty($range['end']) && Schema::hasColumn('registrants', 'created_at')) {
                $query->where('r.created_at', '<=', $range['end'].' 23:59:59');
            }
            $rows = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return [
            'title' => 'Registration Portals Report — '.$range['label'],
            'headers' => ['Portal', 'Reg No', 'Name', 'Email', 'Phone', 'Status', 'Payment', 'Attendance'],
            'rows' => array_map(static fn (array $row): array => [
                $row['portal_title'] ?? ($row['portal_slug'] ?? ''),
                $row['registration_number'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['status'] ?? '',
                $row['payment_status'] ?? '',
                $row['attendance_status'] ?? '',
            ], $rows),
            'row_count' => count($rows),
            'meta' => ['type' => 'registration_portals'],
        ];
    }

    /**
     * @param  list<string>  $orderBy
     * @return list<array<string, mixed>>
     */
    private function tableRows(string $table, string $dateColumn, array $range, array $orderBy = []): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table)->limit(10000);
        foreach ($orderBy as $col) {
            if (Schema::hasColumn($table, $col)) {
                $query->orderBy($col);
            }
        }
        if ($orderBy === [] && Schema::hasColumn($table, 'id')) {
            $query->orderByDesc('id');
        }

        if (Schema::hasColumn($table, $dateColumn)) {
            if (! empty($range['start'])) {
                $query->where($dateColumn, '>=', $range['start']);
            }
            if (! empty($range['end'])) {
                $query->where($dateColumn, '<=', $range['end']);
            }
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }
}
