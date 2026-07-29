<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class TeacherReadService
{
    private const TEACHER_STATUSES = ['active', 'suspended'];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listTeachers(string $query, string $status, int $page, int $perPage): array
    {
        $base = DB::table('sunday_school_teachers as t')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 't.class_id')
            ->leftJoin('admins as a', 'a.id', '=', 't.admin_id');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('t.full_name', 'like', $like)
                    ->orWhere('t.email', 'like', $like)
                    ->orWhere('t.phone', 'like', $like)
                    ->orWhere('t.teacher_code', 'like', $like);
            });
        }

        if ($status !== '' && in_array($status, self::TEACHER_STATUSES, true)) {
            $base->where('t.status', $status);
        }

        $total = (int) (clone $base)->count();
        $offset = max(0, ($page - 1) * $perPage);

        $items = (clone $base)
            ->select(
                't.*',
                'c.class_name',
                DB::raw('a.full_name AS admin_name'),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM sunday_school_students s
                    WHERE s.class_id = t.class_id
                      AND s.status = 'active'
                ) AS student_count")
            )
            ->orderBy('t.full_name')
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
