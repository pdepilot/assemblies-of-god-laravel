<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class AwardReadService
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listAwards(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));

        $base = DB::table('sunday_school_awards as a')
            ->leftJoin('sunday_school_students as s', function ($join) {
                $join->on('a.recipient_id', '=', 's.id')
                    ->where('a.recipient_type', '=', 'student');
            })
            ->leftJoin('sunday_school_classes as sc', 'sc.id', '=', 's.class_id')
            ->leftJoin('sunday_school_classes as cc', function ($join) {
                $join->on('a.recipient_id', '=', 'cc.id')
                    ->where('a.recipient_type', '=', 'class');
            });

        if ($status !== '') {
            $base->where('a.status', $status);
        }

        $total = (int) (clone $base)->count('a.id');
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = (clone $base)
            ->selectRaw("
                a.*,
                CASE
                    WHEN a.recipient_type = 'student' THEN COALESCE(sc.class_name, a.class_name)
                    WHEN a.recipient_type = 'class' THEN COALESCE(cc.class_name, a.class_name)
                    ELSE a.class_name
                END AS class_name,
                CASE
                    WHEN a.recipient_type = 'student' THEN COALESCE(s.class_id, a.class_id)
                    WHEN a.recipient_type = 'class' THEN COALESCE(cc.id, a.class_id)
                    ELSE a.class_id
                END AS class_id
            ")
            ->orderByDesc('a.recommended_at')
            ->orderByDesc('a.id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }
}
