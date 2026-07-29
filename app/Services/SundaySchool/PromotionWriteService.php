<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class PromotionWriteService
{
    public function __construct(
        private readonly ReportReadService $reportRead,
    ) {}

    public function promoteStudent(int $studentId, int $toClassId, int $adminId, ?string $notes = null): void
    {
        $student = SundaySchoolStudent::query()->find($studentId);
        if (! $student) {
            throw new InvalidArgumentException('Student not found.');
        }

        $fromClassId = (int) ($student->class_id ?? 0);
        $student->update(['class_id' => $toClassId]);
        $this->recordPromotion($studentId, $fromClassId ?: null, $toClassId, 'promote', $adminId, $notes);
    }

    public function graduateStudent(int $studentId, int $adminId, ?string $notes = null): void
    {
        $student = SundaySchoolStudent::query()->find($studentId);
        if (! $student) {
            throw new InvalidArgumentException('Student not found.');
        }

        $fromClassId = (int) ($student->class_id ?? 0);
        $student->update(['status' => 'graduated', 'class_id' => null]);
        $this->recordPromotion($studentId, $fromClassId ?: null, null, 'graduate', $adminId, $notes);
    }

    /**
     * @return array{promoted: int, graduated: int}
     */
    public function bulkPromoteClass(int $fromClassId, ?int $toClassId, int $adminId): array
    {
        $ids = SundaySchoolStudent::query()
            ->where('class_id', $fromClassId)
            ->where('status', 'active')
            ->pluck('id');

        $promoted = 0;
        $graduated = 0;

        foreach ($ids as $studentId) {
            if ($toClassId) {
                $this->promoteStudent((int) $studentId, $toClassId, $adminId, 'Year-end bulk promotion');
                $promoted++;
            } else {
                $this->graduateStudent((int) $studentId, $adminId, 'Year-end graduation');
                $graduated++;
            }
        }

        return ['promoted' => $promoted, 'graduated' => $graduated];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function evaluatePromotionYear(int $year, int $adminId): array
    {
        if (! Schema::hasTable('sunday_school_promotion_evaluations')) {
            return [];
        }

        $scope = ['is_admin' => true, 'is_teacher' => false, 'class_ids' => []];
        $students = $this->reportRead->studentRankings($scope, 5000);
        $results = [];

        foreach ($students as $stu) {
            $attScore = (float) ($stu['attendance_score'] ?? 0);
            $mvScore = (float) ($stu['memory_verse_score'] ?? 0);
            $partScore = (float) ($stu['participation_score'] ?? 0);
            $punctScore = (float) ($stu['punctuality_score'] ?? 0);
            $overall = (float) ($stu['overall_score'] ?? 0);
            $meets = $attScore >= 60 && $mvScore >= 40 && $overall >= 55;
            $rec = $meets ? 'promote' : ($overall >= 45 ? 'review' : 'repeat');

            DB::table('sunday_school_promotion_evaluations')->updateOrInsert(
                ['student_id' => (int) $stu['id'], 'promotion_year' => $year],
                [
                    'class_id' => (int) ($stu['class_id'] ?? 0),
                    'attendance_score' => $attScore,
                    'memory_verse_score' => $mvScore,
                    'participation_score' => $partScore,
                    'punctuality_score' => $punctScore,
                    'overall_score' => $overall,
                    'meets_requirements' => $meets,
                    'recommendation' => $rec,
                    'evaluated_at' => now(),
                    'evaluated_by' => $adminId,
                ],
            );

            $results[] = [
                'student_id' => (int) $stu['id'],
                'full_name' => $stu['full_name'],
                'class_name' => $stu['class_name'] ?? '',
                'overall_score' => $overall,
                'meets_requirements' => $meets,
                'recommendation' => $rec,
            ];
        }

        usort($results, static fn ($a, $b) => ($b['overall_score'] ?? 0) <=> ($a['overall_score'] ?? 0));

        return $results;
    }

    private function recordPromotion(int $studentId, ?int $fromClassId, ?int $toClassId, string $type, int $adminId, ?string $notes): void
    {
        if (! Schema::hasTable('sunday_school_promotions')) {
            return;
        }

        DB::table('sunday_school_promotions')->insert([
            'student_id' => $studentId,
            'from_class_id' => $fromClassId,
            'to_class_id' => $toClassId,
            'promotion_type' => $type,
            'promotion_year' => (int) date('Y'),
            'notes' => $notes,
            'promoted_by' => $adminId,
            'created_at' => now(),
        ]);
    }
}
