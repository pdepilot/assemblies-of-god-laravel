<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class ClassReadService
{
    private const CLASS_STATUSES = ['active', 'archived'];

    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array{
     *   is_teacher: bool,
     *   teacher_id: int|null,
     *   class_ids: list<int>,
     *   is_admin: bool,
     *   is_superintendent: bool,
     *   can_view_correction_audit: bool
     * }
     */
    public function getAccessScope(array $admin): array
    {
        return $this->scope->getAccessScope($admin);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listClasses(array $scope, string $status, int $page, int $perPage): array
    {
        $effectiveStatus = ($status !== '' && in_array($status, self::CLASS_STATUSES, true))
            ? $status
            : 'active';

        $offset = max(0, ($page - 1) * $perPage);

        $base = DB::table('sunday_school_classes as c')
            ->leftJoin('sunday_school_teachers as a', 'a.id', '=', 'c.assistant_teacher_id');

        $base->where('c.status', $effectiveStatus);
        $this->scope->applyClassScope($base, $scope, 'c', 'id');

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->select(
                'c.*',
                DB::raw("COALESCE(
                    (SELECT tp.full_name FROM sunday_school_teachers tp WHERE tp.id = c.teacher_id LIMIT 1),
                    (SELECT tp.full_name FROM sunday_school_teachers tp
                        WHERE tp.class_id = c.id AND tp.status = 'active'
                        ORDER BY tp.id ASC
                        LIMIT 1)
                ) AS teacher_name"),
                DB::raw('a.full_name AS assistant_name'),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM sunday_school_students s
                    WHERE s.class_id = c.id
                      AND s.status = 'active'
                ) AS student_count")
            )
            ->orderBy('c.class_name', 'asc')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        return ['items' => $items, 'total' => $total];
    }
}
