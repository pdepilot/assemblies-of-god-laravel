<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class OfferingReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: float}
     */
    public function listOfferings(array $scope, int $classId, string $from, string $to): array
    {
        $base = DB::table('sunday_school_offerings as o')
            ->join('sunday_school_students as s', 's.id', '=', 'o.student_id')
            ->join('sunday_school_classes as c', 'c.id', '=', 'o.class_id')
            ->whereBetween('o.offering_date', [$from, $to]);

        $this->scope->applyClassScope($base, $scope, 'o', 'class_id');

        if ($classId > 0) {
            $base->where('o.class_id', $classId);
        }

        $items = (clone $base)
            ->select('o.*', 's.full_name as student_name', 'c.class_name')
            ->orderByDesc('o.offering_date')
            ->orderBy('s.full_name')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $total = array_sum(array_map(static fn ($row) => (float) ($row['amount'] ?? 0), $items));

        return [
            'items' => $items,
            'total' => round($total, 2),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeStudentsForClass(int $classId): array
    {
        return DB::table('sunday_school_students')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'student_code'])
            ->map(static fn ($row) => (array) $row)
            ->all();
    }
}
