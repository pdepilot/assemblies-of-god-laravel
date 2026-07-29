<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AttendanceReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getSheet(int $classId, string $date, array $scope): array
    {
        if (! ($scope['is_admin'] ?? false) && ! in_array($classId, array_map('intval', $scope['class_ids'] ?? []), true)) {
            throw new InvalidArgumentException('You do not have access to this class.');
        }

        $offeringSub = DB::table('sunday_school_offerings')
            ->select('student_id', DB::raw('SUM(amount) AS offering_amount'), DB::raw('1 AS has_offering'))
            ->where('class_id', $classId)
            ->where('offering_date', $date)
            ->groupBy('student_id');

        $memoryVerseSub = DB::table('sunday_school_memory_verses')
            ->select(
                'student_id',
                DB::raw("MAX(CASE WHEN score IN ('excellent', 'passed') THEN 1 ELSE 0 END) AS memory_verse_passed"),
                DB::raw('1 AS mv_record_exists'),
            )
            ->where('class_id', $classId)
            ->where('recitation_date', $date)
            ->groupBy('student_id');

        return DB::table('sunday_school_students as s')
            ->leftJoin('sunday_school_attendance as a', function ($join) use ($date) {
                $join->on('a.student_id', '=', 's.id')
                    ->where('a.attendance_date', '=', $date);
            })
            ->leftJoinSub($offeringSub, 'o', 'o.student_id', '=', 's.id')
            ->leftJoinSub($memoryVerseSub, 'mv', 'mv.student_id', '=', 's.id')
            ->where('s.class_id', $classId)
            ->where('s.status', 'active')
            ->orderBy('s.full_name')
            ->select(
                's.id as student_id',
                's.full_name',
                's.student_code',
                'a.id as attendance_id',
                'a.status',
                'a.arrival_status',
                DB::raw('COALESCE(o.offering_amount, 0) AS offering_amount'),
                DB::raw('COALESCE(mv.memory_verse_passed, 0) AS memory_verse_passed'),
                DB::raw('CASE
                    WHEN a.id IS NOT NULL
                      OR COALESCE(o.has_offering, 0) = 1
                      OR COALESCE(mv.mv_record_exists, 0) = 1
                    THEN 1 ELSE 0
                END AS has_register_data'),
            )
            ->get()
            ->map(static function ($row) {
                $data = (array) $row;
                $hasRegisterData = (int) ($data['has_register_data'] ?? 0) === 1;

                return [
                    'student_id' => (int) $data['student_id'],
                    'full_name' => (string) $data['full_name'],
                    'student_code' => (string) $data['student_code'],
                    'attendance_id' => $data['attendance_id'] !== null ? (int) $data['attendance_id'] : null,
                    'status' => $data['status'] ?? 'present',
                    'arrival_status' => $data['arrival_status'] ?? 'unknown',
                    'offering_amount' => round((float) ($data['offering_amount'] ?? 0), 2),
                    'memory_verse_passed' => (int) ($data['memory_verse_passed'] ?? 0) === 1,
                    'has_register_data' => $hasRegisterData,
                    'is_marked' => $hasRegisterData,
                ];
            })
            ->all();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listRemovals(int $page, int $perPage): array
    {
        $base = DB::table('sunday_school_attendance_removals as r')
            ->leftJoin('admins as a', 'a.id', '=', 'r.removed_by')
            ->leftJoin('sunday_school_teachers as t', 't.id', '=', 'r.teacher_id');

        $total = (int) (clone $base)->count();
        $offset = max(0, ($page - 1) * $perPage);

        $items = (clone $base)
            ->select(
                'r.*',
                DB::raw('a.full_name AS removed_by_name'),
                'a.email as removed_by_email',
                DB::raw('t.full_name AS teacher_name'),
            )
            ->orderByDesc('r.removed_at')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $pages = max(1, (int) ceil($total / max(1, $perPage)));

        return [
            'items' => $items,
            'total' => $total,
            'page' => max(1, $page),
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }
}
