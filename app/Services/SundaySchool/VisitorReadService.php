<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class VisitorReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listVisitors(array $accessScope, string $from, string $to, int $classId): array
    {
        $base = DB::table('sunday_school_visitors as v')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'v.class_id')
            ->whereBetween('v.visit_date', [$from, $to]);

        if ($classId > 0) {
            $base->where('v.class_id', $classId);
        }

        if ($accessScope['is_teacher'] ?? false) {
            $classIds = array_map('intval', $accessScope['class_ids'] ?? []);
            if ($classIds === []) {
                return ['items' => [], 'total' => 0];
            }
            $base->whereIn('v.class_id', $classIds);
        }

        $items = (clone $base)
            ->select('v.*', 'c.class_name')
            ->orderByDesc('v.visit_date')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        return ['items' => $items, 'total' => count($items)];
    }
}
