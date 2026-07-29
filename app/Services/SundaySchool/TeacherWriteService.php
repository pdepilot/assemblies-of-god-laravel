<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TeacherWriteService
{
    private const TEACHER_STATUSES = ['active', 'suspended'];
    private const CHURCH_STATUSES = ['full_member', 'baptized', 'visitor', 'unbaptized'];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $payload = $this->validateTeacher($data);

        if ($id > 0) {
            $teacher = SundaySchoolTeacher::query()->findOrFail($id);
            $previousClassId = (int) ($teacher->class_id ?? 0);
            $teacher->update($payload);
            $teacher = $teacher->fresh();
            $this->syncClassTeacherAssignment($teacher, $previousClassId);

            return $teacher->toArray();
        }

        $payload['teacher_code'] = $this->nextCode('SS-TCH', 'sunday_school_teachers', 'teacher_code');
        $teacher = SundaySchoolTeacher::query()->create($payload);
        $this->syncClassTeacherAssignment($teacher, 0);

        return $teacher->toArray();
    }

    public function setStatus(int $id, string $status, int $adminId): void
    {
        if (! in_array($status, self::TEACHER_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid teacher status.');
        }

        SundaySchoolTeacher::query()->whereKey($id)->update(['status' => $status]);
    }

    public function delete(int $id, int $adminId): void
    {
        SundaySchoolClass::query()->where('teacher_id', $id)->update(['teacher_id' => null]);
        SundaySchoolClass::query()->where('assistant_teacher_id', $id)->update(['assistant_teacher_id' => null]);
        SundaySchoolTeacher::query()->whereKey($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateTeacher(array $data): array
    {
        $name = trim((string) ($data['full_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Teacher name is required.');
        }

        $membership = $this->normalizeChurchStatus($data['membership_status'] ?? 'unbaptized') ?? 'unbaptized';
        $status = (string) ($data['status'] ?? 'active');

        return [
            'full_name' => $name,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'gender' => in_array($data['gender'] ?? '', ['male', 'female', 'unspecified'], true) ? $data['gender'] : 'unspecified',
            'date_joined' => ($data['date_joined'] ?? '') ?: null,
            'class_id' => ($data['class_id'] ?? '') !== '' ? (int) $data['class_id'] : null,
            'qualification' => trim((string) ($data['qualification'] ?? '')) ?: null,
            'ministry_position' => trim((string) ($data['ministry_position'] ?? '')) ?: null,
            'admin_id' => ($data['admin_id'] ?? '') !== '' ? (int) $data['admin_id'] : null,
            'status' => in_array($status, self::TEACHER_STATUSES, true) ? $status : 'active',
            'membership_status' => $membership,
        ];
    }

    private function nextCode(string $prefix, string $table, string $column): string
    {
        $last = DB::table($table)->orderByDesc('id')->value($column);
        $num = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $num = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    private function syncClassTeacherAssignment(SundaySchoolTeacher $teacher, int $previousClassId): void
    {
        $teacherId = (int) $teacher->id;
        $classId = (int) ($teacher->class_id ?? 0);

        if ($previousClassId > 0 && $previousClassId !== $classId) {
            SundaySchoolClass::query()
                ->whereKey($previousClassId)
                ->where('teacher_id', $teacherId)
                ->update(['teacher_id' => null]);
        }

        SundaySchoolClass::query()
            ->where('teacher_id', $teacherId)
            ->when($classId > 0, fn ($query) => $query->where('id', '!=', $classId))
            ->update(['teacher_id' => null]);

        if ($classId > 0) {
            SundaySchoolClass::query()
                ->whereKey($classId)
                ->update(['teacher_id' => $teacherId]);
        }
    }

    private function normalizeChurchStatus(mixed $raw): ?string
    {
        $status = (string) $raw;
        if ($status === 'member') {
            return 'full_member';
        }
        if ($status === 'regular' || $status === 'not_baptized') {
            return 'unbaptized';
        }
        if (in_array($status, self::CHURCH_STATUSES, true)) {
            return $status;
        }

        return null;
    }
}
