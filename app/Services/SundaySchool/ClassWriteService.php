<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ClassWriteService
{
    private const CLASS_STATUSES = ['active', 'archived'];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $payload = $this->validateClass($data);

        if ($id > 0) {
            $class = SundaySchoolClass::query()->findOrFail($id);
            $previousTeacherId = (int) ($class->teacher_id ?? 0);
            $class->update($payload);
            $this->syncTeacherClassLinks(
                $id,
                $payload['teacher_id'] ?? null,
                $previousTeacherId,
            );
            $this->syncAwardNames($id, $payload['class_name']);

            return $this->getClass($id) ?? [];
        }

        $payload['class_code'] = $payload['class_code'] !== ''
            ? $payload['class_code']
            : $this->nextCode('SS-CLS', 'sunday_school_classes', 'class_code');

        $class = SundaySchoolClass::query()->create($payload);
        $this->syncTeacherClassLinks(
            (int) $class->id,
            $payload['teacher_id'] ?? null,
            0,
        );

        return $this->getClass((int) $class->id) ?? [];
    }

    public function archive(int $id, int $adminId): void
    {
        SundaySchoolClass::query()
            ->whereKey($id)
            ->update(['status' => 'archived']);
    }

    public function delete(int $id, int $adminId): void
    {
        $hasStudents = SundaySchoolStudent::query()
            ->where('class_id', $id)
            ->exists();

        if ($hasStudents) {
            throw new InvalidArgumentException('Cannot delete a class with enrolled students. Archive it instead.');
        }

        SundaySchoolClass::query()->whereKey($id)->delete();
    }

    public function seedDefaultClasses(int $adminId): int
    {
        $defaults = [
            ['Beginner Class', '3-5'],
            ['Children Class', '6-9'],
            ['Teens Class', '10-14'],
            ['Youth Class', '15-19'],
            ['Adult Class', '20+'],
            ['Couples Class', 'Adults'],
            ['New Converts Class', 'All ages'],
        ];

        $count = 0;

        foreach ($defaults as [$name, $age]) {
            $exists = SundaySchoolClass::query()
                ->where('class_name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->save([
                'class_name' => $name,
                'age_range' => $age,
                'description' => $name.' — Sunday School',
                'teacher_id' => null,
                'assistant_teacher_id' => null,
                'max_capacity' => 50,
                'status' => 'active',
            ], $adminId);

            $count++;
        }

        return $count;
    }

    /** @return array<string, mixed>|null */
    public function getClass(int $id): ?array
    {
        $row = SundaySchoolClass::query()->find($id);

        return $row ? $row->toArray() : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateClass(array $data): array
    {
        $name = trim((string) ($data['class_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Class name is required.');
        }

        $status = (string) ($data['status'] ?? 'active');

        return [
            'class_name' => $name,
            'class_code' => trim((string) ($data['class_code'] ?? '')),
            'age_range' => trim((string) ($data['age_range'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'teacher_id' => ($data['teacher_id'] ?? '') !== '' ? (int) $data['teacher_id'] : null,
            'assistant_teacher_id' => ($data['assistant_teacher_id'] ?? '') !== '' ? (int) $data['assistant_teacher_id'] : null,
            'max_capacity' => max(1, (int) ($data['max_capacity'] ?? 50)),
            'status' => in_array($status, self::CLASS_STATUSES, true) ? $status : 'active',
        ];
    }

    private function syncTeacherClassLinks(int $classId, ?int $teacherId, int $previousTeacherId): void
    {
        if ($previousTeacherId > 0 && $previousTeacherId !== $teacherId) {
            SundaySchoolTeacher::query()
                ->whereKey($previousTeacherId)
                ->where('class_id', $classId)
                ->update(['class_id' => null]);
        }

        if ($teacherId !== null && $teacherId > 0) {
            SundaySchoolTeacher::query()
                ->whereKey($teacherId)
                ->update(['class_id' => $classId]);
        }
    }

    private function syncAwardNames(int $classId, string $className): void
    {
        if (! Schema::hasTable('sunday_school_awards')) {
            return;
        }

        DB::table('sunday_school_awards')
            ->where('class_id', $classId)
            ->update(['class_name' => $className]);

        DB::table('sunday_school_awards')
            ->where('recipient_type', 'class')
            ->where('recipient_id', $classId)
            ->update([
                'class_name' => $className,
                'recipient_name' => $className,
            ]);
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
}
