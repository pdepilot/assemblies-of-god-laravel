<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReportReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardStats(array $accessScope): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $studentBase = DB::table('sunday_school_students as s')
            ->join('sunday_school_classes as c', 'c.id', '=', 's.class_id')
            ->where('s.status', 'active');
        $this->scope->applyClassScope($studentBase, $accessScope, 's', 'class_id');
        $totalStudents = (int) (clone $studentBase)->count();

        $totalTeachers = ($accessScope['is_teacher'] ?? false)
            ? 1
            : (int) DB::table('sunday_school_teachers')->where('status', 'active')->count();

        $classBase = DB::table('sunday_school_classes as c')->where('status', 'active');
        $this->scope->applyClassScope($classBase, $accessScope, 'c', 'id');
        $totalClasses = (int) (clone $classBase)->count();

        $attToday = $this->attendanceCounts($today, $today, $accessScope);
        $attMonth = $this->attendanceCounts($monthStart, $today, $accessScope);

        $offToday = $this->offeringTotal($today, $today, $accessScope);
        $offMonth = $this->offeringTotal($monthStart, $today, $accessScope);
        $offAllTime = $this->offeringAllTimeTotal($accessScope);

        $mvBase = DB::table('sunday_school_memory_verses as mv')
            ->where('mv.recitation_date', '>=', $monthStart)
            ->whereIn('mv.score', ['excellent', 'passed']);
        $this->scope->applyClassScope($mvBase, $accessScope, 'mv', 'class_id');
        $mvMonth = (int) (clone $mvBase)->count();

        return [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classes' => $totalClasses,
            'attendance_today' => $attToday['present'] + $attToday['absent'] + $attToday['excused'],
            'attendance_month' => $attMonth['present'] + $attMonth['absent'] + $attMonth['excused'],
            'offering_today' => $offToday,
            'offering_month' => $offMonth,
            'offering_total' => $offAllTime,
            'students_present_today' => $attToday['present'],
            'students_absent_today' => $attToday['absent'],
            'early_arrivals_today' => $attToday['early'],
            'late_arrivals_today' => $attToday['late'],
            'memory_verse_completions' => $mvMonth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generateClassReport(int $classId, string $date, array $accessScope, ?int $adminId = null): array
    {
        if ($classId <= 0) {
            throw new InvalidArgumentException('Please select a class.');
        }

        if (! ($accessScope['is_admin'] ?? false) && ! in_array($classId, array_map('intval', $accessScope['class_ids'] ?? []), true)) {
            throw new InvalidArgumentException('You do not have access to this class.');
        }

        $classScope = [
            'is_admin' => false,
            'is_teacher' => false,
            'class_ids' => [$classId],
        ];

        $att = $this->attendanceCounts($date, $date, $classScope);

        $students = (int) DB::table('sunday_school_students')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->count();

        $offerings = (float) DB::table('sunday_school_offerings')
            ->where('class_id', $classId)
            ->whereDate('offering_date', $date)
            ->sum('amount');

        $mvPass = (int) DB::table('sunday_school_memory_verses')
            ->where('class_id', $classId)
            ->whereDate('recitation_date', $date)
            ->whereIn('score', ['excellent', 'passed'])
            ->count();

        $rate = $students > 0 ? round(($att['present'] / $students) * 100, 1) : 0.0;

        $report = [
            'class_id' => $classId,
            'report_date' => $date,
            'total_students' => $students,
            'present_students' => $att['present'],
            'absent_students' => $att['absent'],
            'offerings' => round($offerings, 2),
            'memory_verse_pass' => $mvPass,
            'attendance_rate' => $rate,
        ];

        DB::table('sunday_school_reports')->insert([
            'report_type' => 'class_daily',
            'class_id' => $classId,
            'report_date' => $date,
            'report_data' => json_encode($report, JSON_THROW_ON_ERROR),
            'generated_by' => $adminId,
            'created_at' => now(),
        ]);

        return $report;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function punctualityReport(array $accessScope, string $from, string $to): array
    {
        $base = DB::table('sunday_school_attendance as a')
            ->join('sunday_school_students as s', 's.id', '=', 'a.student_id')
            ->join('sunday_school_classes as c', 'c.id', '=', 'a.class_id')
            ->whereBetween('a.attendance_date', [$from, $to])
            ->where('a.status', 'present');

        $this->scope->applyClassScope($base, $accessScope, 'a', 'class_id');

        return $base
            ->select(
                's.full_name',
                'c.class_name',
                DB::raw("SUM(CASE WHEN a.arrival_status = 'early' THEN 1 ELSE 0 END) AS early_count"),
                DB::raw("SUM(CASE WHEN a.arrival_status = 'late' THEN 1 ELSE 0 END) AS late_count"),
                DB::raw('COUNT(a.id) AS total'),
            )
            ->groupBy('s.id', 's.full_name', 'c.class_name')
            ->orderByDesc('early_count')
            ->orderBy('late_count')
            ->limit(30)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function studentRankings(array $accessScope, int $limit = 20): array
    {
        $base = DB::table('sunday_school_students as s')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 's.class_id')
            ->where('s.status', 'active');

        $this->scope->applyClassScope($base, $accessScope, 's', 'class_id');

        $students = $base
            ->select('s.id', 's.full_name', 's.student_code', 's.class_id', 'c.class_name')
            ->orderBy('s.full_name')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        if ($students === []) {
            return [];
        }

        $ids = array_map(static fn ($stu) => (int) $stu['id'], $students);
        $perfMap = $this->batchStudentPerformance($ids);

        $ranked = [];
        foreach ($students as $student) {
            $sid = (int) $student['id'];
            $ranked[] = array_merge($student, $perfMap[$sid] ?? $this->emptyPerformance());
        }

        usort($ranked, static fn ($a, $b) => ($b['overall_score'] ?? 0) <=> ($a['overall_score'] ?? 0));

        return array_slice($ranked, 0, max(1, $limit));
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

    private function offeringAllTimeTotal(array $accessScope): float
    {
        $base = DB::table('sunday_school_offerings as o');
        $this->scope->applyClassScope($base, $accessScope, 'o', 'class_id');

        return round((float) (clone $base)->sum('amount'), 2);
    }

    /**
     * @param  list<int>  $studentIds
     * @return array<int, array<string, float|int>>
     */
    private function batchStudentPerformance(array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if ($studentIds === []) {
            return [];
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $attRows = DB::table('sunday_school_attendance')
            ->select('student_id')
            ->selectRaw(
                "SUM(CASE WHEN attendance_date >= ? THEN 1 ELSE 0 END) AS month_total,
                 SUM(CASE WHEN attendance_date >= ? AND status = 'present' THEN 1 ELSE 0 END) AS month_present",
                [$monthStart, $monthStart],
            )
            ->whereIn('student_id', $studentIds)
            ->where('attendance_date', '<=', $today)
            ->groupBy('student_id')
            ->get();

        $attById = [];
        foreach ($attRows as $row) {
            $attById[(int) $row->student_id] = (array) $row;
        }

        $mvById = DB::table('sunday_school_memory_verses')
            ->select('student_id', DB::raw('COUNT(*) AS cnt'))
            ->whereIn('student_id', $studentIds)
            ->where('recitation_date', '>=', $monthStart)
            ->whereIn('score', ['excellent', 'passed'])
            ->groupBy('student_id')
            ->pluck('cnt', 'student_id')
            ->map(static fn ($v) => (int) $v)
            ->all();

        $punctRows = DB::table('sunday_school_attendance')
            ->select(
                'student_id',
                DB::raw("SUM(CASE WHEN arrival_status = 'early' THEN 1 ELSE 0 END) AS early"),
                DB::raw("SUM(CASE WHEN arrival_status = 'late' THEN 1 ELSE 0 END) AS late"),
                DB::raw('COUNT(*) AS total'),
            )
            ->whereIn('student_id', $studentIds)
            ->where('attendance_date', '>=', $monthStart)
            ->where('status', 'present')
            ->groupBy('student_id')
            ->get();

        $punctById = [];
        foreach ($punctRows as $row) {
            $punctById[(int) $row->student_id] = (array) $row;
        }

        $offById = DB::table('sunday_school_offerings')
            ->select('student_id', DB::raw('COUNT(DISTINCT offering_date) AS days'))
            ->whereIn('student_id', $studentIds)
            ->where('offering_date', '>=', $monthStart)
            ->groupBy('student_id')
            ->pluck('days', 'student_id')
            ->map(static fn ($v) => (int) $v)
            ->all();

        $out = [];
        foreach ($studentIds as $sid) {
            $att = $attById[$sid] ?? [];
            $monthTotal = (int) ($att['month_total'] ?? 0);
            $monthPresent = (int) ($att['month_present'] ?? 0);
            $attScore = $monthTotal > 0 ? round(($monthPresent / $monthTotal) * 100, 1) : 0.0;
            $mvScore = min(100, ($mvById[$sid] ?? 0) * 10);
            $p = $punctById[$sid] ?? [];
            $pTotal = (int) ($p['total'] ?? 0);
            $punctScore = $pTotal > 0
                ? (int) round(((int) ($p['early'] ?? 0) / $pTotal) * 100 - ((int) ($p['late'] ?? 0) / $pTotal) * 30)
                : 50;
            $punctScore = max(0, min(100, $punctScore));
            $offScore = min(100, ($offById[$sid] ?? 0) * 25);
            $participation = round(($attScore + $mvScore + $punctScore + $offScore) / 4, 1);
            $overall = round($attScore * 0.35 + $mvScore * 0.25 + $punctScore * 0.2 + $offScore * 0.2, 1);
            $out[$sid] = [
                'attendance_score' => $attScore,
                'memory_verse_score' => $mvScore,
                'punctuality_score' => $punctScore,
                'offering_score' => $offScore,
                'participation_score' => $participation,
                'overall_score' => $overall,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, float|int>
     */
    private function emptyPerformance(): array
    {
        return [
            'attendance_score' => 0,
            'memory_verse_score' => 0,
            'punctuality_score' => 50,
            'offering_score' => 0,
            'participation_score' => 12.5,
            'overall_score' => 10.0,
        ];
    }
}
