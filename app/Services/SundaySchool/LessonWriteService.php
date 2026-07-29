<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolLesson;
use InvalidArgumentException;

final class LessonWriteService
{
    public function __construct(
        private readonly SundaySchoolScopeService $scope,
        private readonly LessonReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data, int $adminId, array $accessScope): array
    {
        $id = (int) ($data['id'] ?? 0);
        $classId = ($data['class_id'] ?? '') !== '' && ($data['class_id'] ?? null) !== null
            ? (int) $data['class_id']
            : null;

        $payload = [
            'class_id' => $classId,
            'lesson_title' => trim((string) ($data['lesson_title'] ?? '')),
            'lesson_date' => (string) ($data['lesson_date'] ?? now()->toDateString()),
            'bible_text' => $this->nullableString($data['bible_text'] ?? null),
            'golden_text' => $this->nullableString($data['golden_text'] ?? null),
            'objectives' => $this->nullableString($data['objectives'] ?? null),
            'teaching_notes' => $this->nullableString($data['teaching_notes'] ?? null),
            'activities' => $this->nullableString($data['activities'] ?? null),
        ];

        if ($payload['lesson_title'] === '') {
            throw new InvalidArgumentException('Lesson title is required.');
        }

        if ($id > 0) {
            $existing = $this->read->getLesson($id);
            if (! $existing) {
                throw new InvalidArgumentException('Lesson not found.');
            }

            $this->scope->assertLessonInScope($accessScope, $existing['class_id'] ?? null);
        }

        $this->scope->assertLessonClassAssignable($accessScope, $classId);

        if ($id > 0) {
            SundaySchoolLesson::query()->whereKey($id)->update($payload);
        } else {
            $payload['created_by'] = $adminId;
            $lesson = SundaySchoolLesson::query()->create($payload);
            $id = (int) $lesson->id;
        }

        return $this->read->getLesson($id) ?? [];
    }

    public function delete(int $id, array $accessScope): void
    {
        $existing = $this->read->getLesson($id);
        if (! $existing) {
            throw new InvalidArgumentException('Lesson not found.');
        }

        if (! ($accessScope['is_admin'] ?? false)) {
            throw new InvalidArgumentException('Access denied.');
        }

        SundaySchoolLesson::query()->whereKey($id)->delete();
    }

    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed !== '' ? $trimmed : null;
    }
}
