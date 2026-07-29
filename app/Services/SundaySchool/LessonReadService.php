<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class LessonReadService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listLessons(array $scope, int $classId, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);

        $base = DB::table('sunday_school_lessons as l')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'l.class_id');

        $this->scope->applyLessonListScope($base, $scope, 'l');

        if ($classId > 0) {
            $base->where(function ($q) use ($classId) {
                $q->where('l.class_id', $classId)
                    ->orWhereNull('l.class_id');
            });
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->select('l.*', 'c.class_name')
            ->orderByDesc('l.lesson_date')
            ->orderByDesc('l.id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();

        $pages = (int) max(1, (int) ceil($total / $perPage));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLesson(int $id): ?array
    {
        $row = DB::table('sunday_school_lessons as l')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'l.class_id')
            ->select('l.*', 'c.class_name')
            ->where('l.id', $id)
            ->first();

        return $row ? (array) $row : null;
    }
}
