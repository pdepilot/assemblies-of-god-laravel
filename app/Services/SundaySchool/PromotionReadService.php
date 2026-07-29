<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PromotionReadService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listEvaluations(int $year): array
    {
        if (! Schema::hasTable('sunday_school_promotion_evaluations')) {
            return [];
        }

        return DB::table('sunday_school_promotion_evaluations as e')
            ->join('sunday_school_students as s', 's.id', '=', 'e.student_id')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'e.class_id')
            ->where('e.promotion_year', $year)
            ->select('e.*', 's.full_name', 'c.class_name')
            ->orderByDesc('e.overall_score')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }
}
