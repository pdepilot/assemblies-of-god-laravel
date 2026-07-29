<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AnalyticsReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
        private readonly ClassReadService $classRead,
        private readonly ReportReadService $reportRead,
        private readonly StudentReadService $studentRead,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getChartData(array $accessScope): array
    {
        return [
            'weekly_attendance' => $this->attendanceTrend('week', $accessScope),
            'monthly_attendance' => $this->attendanceTrend('month', $accessScope),
            'offering_trends' => $this->offeringTrend($accessScope),
            'class_performance' => $this->classPerformance($accessScope),
            'teacher_performance' => $this->teacherPerformance($accessScope),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTeacherAnalytics(array $accessScope, string $period = 'month'): array
    {
        [$from, $to] = $this->periodBounds($period);
        $classMetrics = $this->batchClassMetrics($from, $to, $accessScope);

        $teachers = DB::table('sunday_school_teachers')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'teacher_code']);

        $rankings = [];
        foreach ($teachers as $teacher) {
            $teacherId = (int) $teacher->id;
            $teacherModel = SundaySchoolTeacher::query()->find($teacherId);
            if (! $teacherModel) {
                continue;
            }

            $classIds = $this->scope->getTeacherClassIds($teacherId, $teacherModel);
            if ($accessScope['is_teacher'] ?? false) {
                $classIds = array_values(array_intersect(
                    $classIds,
                    array_map('intval', $accessScope['class_ids'] ?? []),
                ));
            }
            if ($classIds === []) {
                continue;
            }

            $present = 0;
            $total = 0;
            $students = 0;
            $className = '';
            foreach ($classIds as $classId) {
                $metrics = $classMetrics[$classId] ?? null;
                if ($metrics === null) {
                    continue;
                }
                $present += (int) $metrics['present'];
                $total += (int) $metrics['att_total'];
                $students += (int) $metrics['student_count'];
                if ($className === '' && ($metrics['class_name'] ?? '') !== '') {
                    $className = (string) $metrics['class_name'];
                }
            }

            $attRate = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;
            $growth = $students > 0 ? min(100, $students) : 0;

            $rankings[] = [
                'id' => $teacherId,
                'full_name' => (string) $teacher->full_name,
                'teacher_code' => (string) $teacher->teacher_code,
                'class_name' => $className,
                'attendance_management_rate' => $attRate,
                'retention_rate' => $attRate,
                'class_growth_pct' => $growth,
                'student_count' => $students,
                'composite_score' => round($attRate * 0.5 + min(100, $growth) * 0.5, 1),
            ];
        }

        usort($rankings, static fn ($a, $b) => ($b['composite_score'] ?? 0) <=> ($a['composite_score'] ?? 0));

        return ['period' => $period, 'from' => $from, 'to' => $to, 'rankings' => $rankings];
    }

    /**
     * @return array<string, mixed>
     */
    public function getClassAnalytics(array $accessScope, string $period = 'month'): array
    {
        [$from, $to] = $this->periodBounds($period);
        $metrics = $this->batchClassMetrics($from, $to, $accessScope);
        $classes = $this->studentRead->activeClassOptions($accessScope);

        $rankings = [];
        foreach ($classes as $class) {
            $classId = (int) $class['id'];
            $metric = $metrics[$classId] ?? [
                'student_count' => 0,
                'attendance_rate' => 0,
                'offering_total' => 0,
                'memory_verse_rate' => 0,
                'composite_score' => 0,
            ];

            $rankings[] = array_merge($class, [
                'student_count' => (int) ($metric['student_count'] ?? 0),
                'attendance_rate' => (float) ($metric['attendance_rate'] ?? 0),
                'offering_total' => (float) ($metric['offering_total'] ?? 0),
                'memory_verse_rate' => (float) ($metric['memory_verse_rate'] ?? 0),
                'composite_score' => (float) ($metric['composite_score'] ?? 0),
            ]);
        }

        usort($rankings, static fn ($a, $b) => ($b['composite_score'] ?? 0) <=> ($a['composite_score'] ?? 0));

        return ['period' => $period, 'from' => $from, 'to' => $to, 'rankings' => $rankings];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuperintendentDashboard(array $accessScope): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        $base = $this->reportRead->getDashboardStats($accessScope);
        $charts = $this->getChartData($accessScope);
        $punct = $this->punctualityStats($monthStart, $today, $accessScope);
        $kpi = $this->kpiAttendanceOfferings($accessScope, $today, $weekStart, $monthStart, $yearStart);

        return [
            'generated_at' => now()->toIso8601String(),
            'kpis' => [
                'attendance_today' => $kpi['att']['today'],
                'attendance_week' => $kpi['att']['week'],
                'attendance_month' => $kpi['att']['month'],
                'attendance_year' => $kpi['att']['year'],
                'offering_today' => $kpi['off']['today'],
                'offering_week' => $kpi['off']['week'],
                'offering_month' => $kpi['off']['month'],
                'offering_year' => $kpi['off']['year'],
                'offering_total' => $base['offering_total'],
                'active_classes' => $base['total_classes'],
                'active_teachers' => $base['total_teachers'],
                'total_students' => $base['total_students'],
                'new_students_week' => $this->countNewStudents(now()->subDays(7)->toDateString(), $today, $accessScope),
                'new_students_month' => $this->countNewStudents($monthStart, $today, $accessScope),
                'visitors_today' => $this->countVisitors($today, $today),
                'visitors_month' => $this->countVisitors($monthStart, $today),
                'memory_verse_rate' => $this->memoryVerseStats($monthStart, $today, $accessScope)['pass_rate'],
                'punctuality_early_pct' => $punct['early_pct'],
                'punctuality_late_pct' => $punct['late_pct'],
                'retention_rate' => $this->retentionRate($accessScope)['rate'],
                'students_present_today' => $base['students_present_today'],
                'students_absent_today' => $base['students_absent_today'],
            ],
            'charts' => array_merge($charts, [
                'attendance_heatmap' => $this->attendanceHeatmap($accessScope, 8),
                'yearly_attendance' => $this->monthlyAttendanceTrend($accessScope),
                'yearly_offerings' => $this->monthlyOfferingTrend($accessScope),
            ]),
            'teacher_analytics' => $this->getTeacherAnalytics($accessScope, 'month'),
            'class_analytics' => $this->getClassAnalytics($accessScope, 'month'),
            'top_performers' => [
                'students' => $this->reportRead->studentRankings($accessScope, 10),
            ],
        ];
    }

    /**
     * @return list<array{label: string, present: int, total: int}>
     */
    private function attendanceTrend(string $period, array $accessScope): array
    {
        $days = $period === 'week' ? 7 : 30;
        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $counts = $this->attendanceCounts($date, $date, $accessScope);
            $result[] = [
                'label' => $period === 'week'
                    ? now()->subDays($i)->format('D')
                    : now()->subDays($i)->format('M j'),
                'present' => $counts['present'],
                'total' => $counts['present'] + $counts['absent'] + $counts['excused'],
            ];
        }

        return $result;
    }

    /**
     * @return list<array{label: string, amount: float}>
     */
    private function offeringTrend(array $accessScope): array
    {
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result[] = [
                'label' => now()->subDays($i)->format('D'),
                'amount' => $this->offeringTotal($date, $date, $accessScope),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{label: string, rate: float}>
     */
    private function classPerformance(array $accessScope): array
    {
        $classes = $this->classRead->listClasses($accessScope, 'active', 1, 50)['items'];
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();
        $result = [];

        foreach ($classes as $class) {
            $classId = (int) $class['id'];
            $students = (int) ($class['student_count'] ?? 0);
            $classScope = ['is_admin' => false, 'is_teacher' => false, 'class_ids' => [$classId]];
            $att = $this->attendanceCounts($monthStart, $today, $classScope);
            $sessions = max(1, (int) ceil((now()->timestamp - now()->startOfMonth()->timestamp) / 604800));
            $expected = $students * $sessions;
            $rate = $expected > 0 ? round(($att['present'] / $expected) * 100, 1) : 0.0;
            $result[] = [
                'label' => (string) $class['class_name'],
                'rate' => min(100, $rate),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{label: string, rate: float}>
     */
    private function teacherPerformance(array $accessScope): array
    {
        if ($accessScope['is_teacher'] ?? false) {
            $teacherId = (int) ($accessScope['teacher_id'] ?? 0);
            $name = (string) DB::table('sunday_school_teachers')->where('id', $teacherId)->value('full_name');

            return [[
                'label' => $name !== '' ? $name : 'You',
                'rate' => $this->teacherAttendanceRate($teacherId),
            ]];
        }

        $teachers = DB::table('sunday_school_teachers')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $result = [];
        foreach ($teachers as $teacher) {
            $result[] = [
                'label' => (string) $teacher->full_name,
                'rate' => $this->teacherAttendanceRate((int) $teacher->id),
            ];
        }

        return $result;
    }

    private function teacherAttendanceRate(int $teacherId): float
    {
        $teacher = SundaySchoolTeacher::query()->find($teacherId);
        if (! $teacher) {
            return 0.0;
        }

        $classIds = $this->scope->getTeacherClassIds($teacherId, $teacher);
        if ($classIds === []) {
            return 0.0;
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();
        $classScope = ['is_admin' => false, 'is_teacher' => false, 'class_ids' => $classIds];
        $att = $this->attendanceCounts($monthStart, $today, $classScope);
        $total = $att['present'] + $att['absent'] + $att['excused'];

        return $total > 0 ? round(($att['present'] / $total) * 100, 1) : 0.0;
    }

    /**
     * @return array{present: int, absent: int, excused: int, early: int, late: int}
     */
    private function attendanceCounts(string $from, string $to, array $accessScope): array
    {
        $base = DB::table('sunday_school_attendance as a')
            ->whereBetween('a.attendance_date', [$from, $to]);
        $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

        $row = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused,
                SUM(CASE WHEN a.arrival_status = 'early' THEN 1 ELSE 0 END) AS early,
                SUM(CASE WHEN a.arrival_status = 'late' THEN 1 ELSE 0 END) AS late
            ")
            ->first();

        return [
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'excused' => (int) ($row->excused ?? 0),
            'early' => (int) ($row->early ?? 0),
            'late' => (int) ($row->late ?? 0),
        ];
    }

    private function offeringTotal(string $from, string $to, array $accessScope): float
    {
        $base = DB::table('sunday_school_offerings as o')
            ->whereBetween('o.offering_date', [$from, $to]);
        $this->scope->applyClassScope($base, $accessScope, 'o', 'class_id');

        return round((float) (clone $base)->sum('amount'), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function batchClassMetrics(string $from, string $to, array $accessScope): array
    {
        $base = DB::table('sunday_school_classes as c')->where('c.status', 'active');
        $this->scope->applyClassScope($base, $accessScope, 'c', 'id');
        $classes = (clone $base)->get(['c.id', 'c.class_name']);

        if ($classes->isEmpty()) {
            return [];
        }

        $ids = $classes->pluck('id')->map(static fn ($id) => (int) $id)->all();

        $studentsByClass = DB::table('sunday_school_students')
            ->select('class_id', DB::raw('COUNT(*) AS cnt'))
            ->where('status', 'active')
            ->whereIn('class_id', $ids)
            ->groupBy('class_id')
            ->pluck('cnt', 'class_id')
            ->map(static fn ($v) => (int) $v)
            ->all();

        $attRows = DB::table('sunday_school_attendance')
            ->select(
                'class_id',
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present"),
                DB::raw('COUNT(*) AS total'),
            )
            ->whereBetween('attendance_date', [$from, $to])
            ->whereIn('class_id', $ids)
            ->groupBy('class_id')
            ->get();

        $attByClass = [];
        foreach ($attRows as $row) {
            $attByClass[(int) $row->class_id] = (array) $row;
        }

        $offByClass = DB::table('sunday_school_offerings')
            ->select('class_id', DB::raw('COALESCE(SUM(amount), 0) AS amount'))
            ->whereBetween('offering_date', [$from, $to])
            ->whereIn('class_id', $ids)
            ->groupBy('class_id')
            ->pluck('amount', 'class_id')
            ->map(static fn ($v) => (float) $v)
            ->all();

        $mvRows = DB::table('sunday_school_memory_verses')
            ->select(
                'class_id',
                DB::raw('COUNT(*) AS total'),
                DB::raw("SUM(CASE WHEN score IN ('excellent','passed') THEN 1 ELSE 0 END) AS passed"),
            )
            ->whereBetween('recitation_date', [$from, $to])
            ->whereIn('class_id', $ids)
            ->groupBy('class_id')
            ->get();

        $mvByClass = [];
        foreach ($mvRows as $row) {
            $mvByClass[(int) $row->class_id] = (array) $row;
        }

        $out = [];
        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $students = $studentsByClass[$classId] ?? 0;
            $att = $attByClass[$classId] ?? ['present' => 0, 'total' => 0];
            $present = (int) ($att['present'] ?? 0);
            $attTotal = (int) ($att['total'] ?? 0);
            $rate = $students > 0 && $attTotal > 0 ? round(($present / $students) * 100, 1) : 0.0;
            $off = $offByClass[$classId] ?? 0.0;
            $mv = $mvByClass[$classId] ?? ['total' => 0, 'passed' => 0];
            $mvTotal = (int) ($mv['total'] ?? 0);
            $mvPass = $mvTotal > 0 ? round(((int) ($mv['passed'] ?? 0) / $mvTotal) * 100, 1) : 0.0;
            $composite = round($rate * 0.4 + $mvPass * 0.3 + min(100, $off / max(1, $students)) * 0.3, 1);

            $out[$classId] = [
                'class_name' => (string) $class->class_name,
                'student_count' => $students,
                'present' => $present,
                'att_total' => $attTotal,
                'attendance_rate' => $rate,
                'offering_total' => $off,
                'memory_verse_rate' => $mvPass,
                'composite_score' => $composite,
            ];
        }

        return $out;
    }

    /**
     * @return array{att: array<string, int>, off: array<string, float>}
     */
    private function kpiAttendanceOfferings(
        array $accessScope,
        string $today,
        string $weekStart,
        string $monthStart,
        string $yearStart,
    ): array {
        $base = DB::table('sunday_school_attendance as a')
            ->whereBetween('a.attendance_date', [$yearStart, $today]);
        $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

        $att = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN a.attendance_date = ? AND a.status = 'present' THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN a.attendance_date BETWEEN ? AND ? AND a.status = 'present' THEN 1 ELSE 0 END) AS week,
                SUM(CASE WHEN a.attendance_date BETWEEN ? AND ? AND a.status = 'present' THEN 1 ELSE 0 END) AS month,
                SUM(CASE WHEN a.attendance_date BETWEEN ? AND ? AND a.status = 'present' THEN 1 ELSE 0 END) AS year
            ", [$today, $weekStart, $today, $monthStart, $today, $yearStart, $today])
            ->first();

        $offBase = DB::table('sunday_school_offerings as o')
            ->whereBetween('o.offering_date', [$yearStart, $today]);
        $this->scope->applyClassScope($offBase, $accessScope, 'o', 'class_id');

        $off = (clone $offBase)
            ->selectRaw('
                SUM(CASE WHEN o.offering_date = ? THEN o.amount ELSE 0 END) AS today,
                SUM(CASE WHEN o.offering_date BETWEEN ? AND ? THEN o.amount ELSE 0 END) AS week,
                SUM(CASE WHEN o.offering_date BETWEEN ? AND ? THEN o.amount ELSE 0 END) AS month,
                SUM(CASE WHEN o.offering_date BETWEEN ? AND ? THEN o.amount ELSE 0 END) AS year
            ', [$today, $weekStart, $today, $monthStart, $today, $yearStart, $today])
            ->first();

        return [
            'att' => [
                'today' => (int) ($att->today ?? 0),
                'week' => (int) ($att->week ?? 0),
                'month' => (int) ($att->month ?? 0),
                'year' => (int) ($att->year ?? 0),
            ],
            'off' => [
                'today' => round((float) ($off->today ?? 0), 2),
                'week' => round((float) ($off->week ?? 0), 2),
                'month' => round((float) ($off->month ?? 0), 2),
                'year' => round((float) ($off->year ?? 0), 2),
            ],
        ];
    }

    /**
     * @return array{pass_rate: float}
     */
    private function memoryVerseStats(string $from, string $to, array $accessScope): array
    {
        $base = DB::table('sunday_school_memory_verses as mv')
            ->whereBetween('mv.recitation_date', [$from, $to]);
        $this->scope->applyClassScope($base, $accessScope, 'mv', 'class_id');

        $row = (clone $base)
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN mv.score IN ('excellent','passed') THEN 1 ELSE 0 END) AS passed
            ")
            ->first();

        $total = (int) ($row->total ?? 0);
        $passed = (int) ($row->passed ?? 0);

        return ['pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0.0];
    }

    /**
     * @return array{early_pct: float, late_pct: float}
     */
    private function punctualityStats(string $from, string $to, array $accessScope): array
    {
        $base = DB::table('sunday_school_attendance as a')
            ->whereBetween('a.attendance_date', [$from, $to]);
        $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

        $row = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN a.arrival_status = 'early' THEN 1 ELSE 0 END) AS early,
                SUM(CASE WHEN a.arrival_status = 'late' THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present
            ")
            ->first();

        $present = (int) ($row->present ?? 0);

        return [
            'early_pct' => $present > 0 ? round(((int) ($row->early ?? 0) / $present) * 100, 1) : 0.0,
            'late_pct' => $present > 0 ? round(((int) ($row->late ?? 0) / $present) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{rate: float}
     */
    private function retentionRate(array $accessScope): array
    {
        $base = DB::table('sunday_school_students as s')->where('s.status', 'active');
        $this->scope->applyClassScope($base, $accessScope, 's', 'class_id');
        $active = (int) (clone $base)->count();

        $retainedBase = DB::table('sunday_school_attendance as a')
            ->join('sunday_school_students as s', 's.id', '=', 'a.student_id')
            ->where('s.status', 'active')
            ->where('a.attendance_date', '>=', now()->startOfMonth()->toDateString())
            ->where('a.status', 'present');
        $this->scope->applyClassScope($retainedBase, $accessScope, 's', 'class_id');
        $retained = (int) (clone $retainedBase)->distinct('a.student_id')->count('a.student_id');

        return ['rate' => $active > 0 ? round(($retained / $active) * 100, 1) : 0.0];
    }

    /**
     * @return list<array{week: string, rate: float}>
     */
    private function attendanceHeatmap(array $accessScope, int $weeks): array
    {
        $result = [];
        for ($w = $weeks - 1; $w >= 0; $w--) {
            $weekStart = now()->startOfWeek()->subWeeks($w)->toDateString();
            $weekEnd = now()->startOfWeek()->subWeeks($w)->endOfWeek()->toDateString();

            $base = DB::table('sunday_school_attendance as a')
                ->whereBetween('a.attendance_date', [$weekStart, $weekEnd]);
            $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

            $row = (clone $base)
                ->selectRaw("
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present,
                    COUNT(*) AS total
                ")
                ->first();

            $total = (int) ($row->total ?? 0);
            $rate = $total > 0 ? round(((int) ($row->present ?? 0) / $total) * 100, 1) : 0.0;
            $result[] = [
                'week' => now()->startOfWeek()->subWeeks($w)->format('M j'),
                'rate' => $rate,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{label: string, present: int}>
     */
    private function monthlyAttendanceTrend(array $accessScope): array
    {
        $result = [];
        for ($m = 11; $m >= 0; $m--) {
            $start = now()->startOfMonth()->subMonths($m);
            $end = $start->copy()->endOfMonth();
            $base = DB::table('sunday_school_attendance as a')
                ->whereBetween('a.attendance_date', [$start->toDateString(), $end->toDateString()])
                ->where('a.status', 'present');
            $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

            $result[] = [
                'label' => $start->format('M Y'),
                'present' => (int) (clone $base)->count(),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{label: string, amount: float}>
     */
    private function monthlyOfferingTrend(array $accessScope): array
    {
        $result = [];
        for ($m = 11; $m >= 0; $m--) {
            $start = now()->startOfMonth()->subMonths($m);
            $end = $start->copy()->endOfMonth();
            $base = DB::table('sunday_school_offerings as o')
                ->whereBetween('o.offering_date', [$start->toDateString(), $end->toDateString()]);
            $this->scope->applyClassScope($base, $accessScope, 'o', 'class_id');

            $result[] = [
                'label' => $start->format('M Y'),
                'amount' => round((float) (clone $base)->sum('amount'), 2),
            ];
        }

        return $result;
    }

    private function countNewStudents(string $from, string $to, array $accessScope): int
    {
        $base = DB::table('sunday_school_students as s')->where('s.status', 'active');
        $this->scope->applyClassScope($base, $accessScope, 's', 'class_id');

        return (int) (clone $base)
            ->whereBetween('s.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->count();
    }

    private function countVisitors(string $from, string $to): int
    {
        if (! Schema::hasTable('sunday_school_visitors')) {
            return 0;
        }

        return (int) DB::table('sunday_school_visitors')
            ->whereBetween('visit_date', [$from, $to])
            ->count();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodBounds(string $period): array
    {
        $to = now()->toDateString();
        $from = match ($period) {
            'quarter' => now()->subMonths(3)->toDateString(),
            'year' => now()->startOfYear()->toDateString(),
            default => now()->startOfMonth()->toDateString(),
        };

        return [$from, $to];
    }

    /**
     * Superintendent offering search by class name and date range.
     *
     * @return array{classes: list<array<string, mixed>>, by_date: list<array<string, mixed>>, grand_total: float, from: string, to: string}
     */
    public function searchClassOfferings(string $query, string $from = '', string $to = ''): array
    {
        $query = trim($query);

        $classBase = DB::table('sunday_school_classes as c')
            ->leftJoin('sunday_school_offerings as o', function ($join) use ($from, $to) {
                $join->on('o.class_id', '=', 'c.id');
                if ($from !== '') {
                    $join->where('o.offering_date', '>=', $from);
                }
                if ($to !== '') {
                    $join->where('o.offering_date', '<=', $to);
                }
            });

        if ($query !== '') {
            $classBase->where('c.class_name', 'like', '%'.$query.'%');
        }

        $classes = $classBase
            ->groupBy('c.id', 'c.class_name')
            ->selectRaw('
                c.id,
                c.class_name,
                COALESCE(SUM(o.amount), 0) AS total,
                COUNT(DISTINCT o.offering_date) AS offering_days,
                MIN(o.offering_date) AS first_date,
                MAX(o.offering_date) AS last_date
            ')
            ->orderBy('c.class_name')
            ->get()
            ->map(function ($row) {
                $item = (array) $row;
                $item['total'] = (float) ($item['total'] ?? 0);

                return $item;
            })
            ->all();

        $dateBase = DB::table('sunday_school_offerings as o')
            ->join('sunday_school_classes as c', 'c.id', '=', 'o.class_id');

        if ($query !== '') {
            $dateBase->where('c.class_name', 'like', '%'.$query.'%');
        }
        if ($from !== '') {
            $dateBase->where('o.offering_date', '>=', $from);
        }
        if ($to !== '') {
            $dateBase->where('o.offering_date', '<=', $to);
        }

        $byDate = $dateBase
            ->groupBy('o.offering_date', 'c.id', 'c.class_name')
            ->selectRaw('o.offering_date, c.class_name, SUM(o.amount) AS amount')
            ->orderByDesc('o.offering_date')
            ->orderBy('c.class_name')
            ->limit(200)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $grand = 0.0;
        foreach ($classes as $class) {
            $grand += (float) ($class['total'] ?? 0);
        }

        return [
            'classes' => $classes,
            'by_date' => $byDate,
            'grand_total' => round($grand, 2),
            'from' => $from,
            'to' => $to,
        ];
    }
}
