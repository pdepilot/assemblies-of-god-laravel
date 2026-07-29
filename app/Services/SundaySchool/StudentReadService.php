<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class StudentReadService
{
    private const STUDENT_STATUSES = ['active', 'archived', 'graduated'];

    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listStudents(array $scope, string $query, int $classId, string $status, int $page, int $perPage): array
    {
        $effectiveStatus = ($status !== '' && in_array($status, self::STUDENT_STATUSES, true))
            ? $status
            : 'active';

        $base = DB::table('sunday_school_students as s')
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 's.class_id');

        $this->scope->applyClassScope($base, $scope, 's', 'class_id');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('s.full_name', 'like', $like)
                    ->orWhere('s.student_code', 'like', $like)
                    ->orWhere('s.parent_name', 'like', $like);
            });
        }

        if ($classId > 0) {
            $base->where('s.class_id', $classId);
        }

        $base->where('s.status', $effectiveStatus);

        $total = (int) (clone $base)->count();
        $offset = max(0, ($page - 1) * $perPage);

        $items = (clone $base)
            ->select('s.*', 'c.class_name')
            ->orderBy('s.full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($row) {
                $item = (array) $row;
                if (! empty($item['date_of_birth'])) {
                    $item['age'] = Carbon::parse($item['date_of_birth'])->age;
                } else {
                    $item['age'] = null;
                }

                return $item;
            })
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

    /** @return list<array<string, mixed>> */
    public function activeClassOptions(array $scope): array
    {
        $query = SundaySchoolClass::query()->where('status', 'active')->orderBy('class_name');

        if (! ($scope['is_admin'] ?? false)) {
            $classIds = array_map('intval', $scope['class_ids'] ?? []);
            if ($classIds === []) {
                return [];
            }
            $query->whereIn('id', $classIds);
        }

        return $query->get(['id', 'class_name', 'class_code'])
            ->map(static fn ($c) => $c->toArray())
            ->all();
    }
}
