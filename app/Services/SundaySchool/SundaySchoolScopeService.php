<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolTeacher;
use Illuminate\Database\Query\Builder;

final class SundaySchoolScopeService
{
    private const SS_ADMIN_ROLES = ['super_admin', 'admin', 'ss_superintendent'];

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
        $role = (string) ($admin['role'] ?? 'admin');
        $adminId = (int) ($admin['id'] ?? 0);

        $isSsAdmin = in_array($role, self::SS_ADMIN_ROLES, true);
        $canViewAudit = in_array($role, ['super_admin', 'ss_superintendent'], true);

        if ($isSsAdmin) {
            return [
                'is_teacher' => false,
                'teacher_id' => null,
                'class_ids' => [],
                'is_admin' => true,
                'is_superintendent' => $role === 'ss_superintendent',
                'can_view_correction_audit' => $canViewAudit,
            ];
        }

        $teacher = SundaySchoolTeacher::query()
            ->where('admin_id', $adminId)
            ->where('status', 'active')
            ->first();

        if (! $teacher) {
            return [
                'is_teacher' => false,
                'teacher_id' => null,
                'class_ids' => [],
                'is_admin' => false,
                'is_superintendent' => false,
                'can_view_correction_audit' => false,
            ];
        }

        $teacherId = (int) $teacher->id;

        return [
            'is_teacher' => true,
            'teacher_id' => $teacherId,
            'class_ids' => $this->getTeacherClassIds($teacherId, $teacher),
            'is_admin' => false,
            'is_superintendent' => false,
            'can_view_correction_audit' => false,
        ];
    }

    /**
     * @return list<int>
     */
    public function getTeacherClassIds(int $teacherId, SundaySchoolTeacher $teacher): array
    {
        $ids = SundaySchoolClass::query()
            ->where('status', 'active')
            ->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                    ->orWhere('assistant_teacher_id', $teacherId);
            })
            ->pluck('id')
            ->map(static fn ($v) => (int) $v)
            ->values()
            ->all();

        $primary = (int) ($teacher->class_id ?? 0);
        if ($primary > 0 && ! in_array($primary, $ids, true)) {
            $ids[] = $primary;
        }

        return $ids;
    }

    public function applyClassScope(Builder $query, array $scope, string $alias, string $column = 'class_id'): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        $classIds = array_values(array_filter(array_map('intval', $scope['class_ids'] ?? [])));

        if ($classIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn("{$alias}.{$column}", $classIds);
    }

    public function assertClassInScope(array $scope, ?int $classId): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        if ($classId === null || $classId === 0) {
            throw new \InvalidArgumentException('Please select a class for this student.');
        }

        $classIds = array_map('intval', $scope['class_ids'] ?? []);
        if (! in_array($classId, $classIds, true)) {
            throw new \InvalidArgumentException('You can only manage students in your assigned classes.');
        }
    }

    public function applyLessonListScope(\Illuminate\Database\Query\Builder $query, array $scope, string $alias = 'l'): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        $classIds = array_values(array_filter(array_map('intval', $scope['class_ids'] ?? [])));

        if ($classIds === []) {
            $query->whereNull("{$alias}.class_id");

            return;
        }

        $query->where(function ($q) use ($alias, $classIds) {
            $q->whereNull("{$alias}.class_id")
                ->orWhereIn("{$alias}.class_id", $classIds);
        });
    }

    public function assertLessonInScope(array $scope, mixed $classId): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        if ($classId === null || $classId === '' || (int) $classId === 0) {
            return;
        }

        $classIds = array_map('intval', $scope['class_ids'] ?? []);
        if (! in_array((int) $classId, $classIds, true)) {
            throw new \InvalidArgumentException('You do not have access to this lesson.');
        }
    }

    public function assertLessonClassAssignable(array $scope, ?int $classId): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        if ($classId === null || $classId === 0) {
            return;
        }

        $classIds = array_map('intval', $scope['class_ids'] ?? []);
        if (! in_array($classId, $classIds, true)) {
            throw new \InvalidArgumentException('You can only assign lessons to your classes.');
        }
    }
}
