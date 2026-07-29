<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolAward;
use Illuminate\Support\Facades\DB;

final class AwardWriteService
{
    /** @var list<array{code: string, category: string, name: string}> */
    public const STUDENT_AWARDS = [
        ['code' => 'best_student_year', 'category' => 'student', 'name' => 'Best Student of the Year'],
        ['code' => 'most_consistent', 'category' => 'student', 'name' => 'Most Consistent Student'],
        ['code' => 'best_attendance', 'category' => 'student', 'name' => 'Best Attendance Award'],
        ['code' => 'most_punctual', 'category' => 'student', 'name' => 'Most Punctual Student'],
        ['code' => 'best_memory_verse', 'category' => 'student', 'name' => 'Best Memory Verse Student'],
        ['code' => 'outstanding_participation', 'category' => 'student', 'name' => 'Outstanding Participation Award'],
        ['code' => 'best_offering', 'category' => 'student', 'name' => 'Best Offering Contributor'],
        ['code' => 'most_improved', 'category' => 'student', 'name' => 'Most Improved Student'],
        ['code' => 'best_new_student', 'category' => 'student', 'name' => 'Best New Student'],
        ['code' => 'leadership', 'category' => 'student', 'name' => 'Leadership Award'],
    ];

    /** @var list<array{code: string, category: string, name: string}> */
    public const TEACHER_AWARDS = [
        ['code' => 'best_teacher_year', 'category' => 'teacher', 'name' => 'Best Teacher of the Year'],
        ['code' => 'most_dedicated', 'category' => 'teacher', 'name' => 'Most Dedicated Teacher'],
        ['code' => 'best_attendance_mgmt', 'category' => 'teacher', 'name' => 'Best Attendance Management'],
        ['code' => 'best_class_growth', 'category' => 'teacher', 'name' => 'Best Class Growth'],
        ['code' => 'best_retention', 'category' => 'teacher', 'name' => 'Best Student Retention'],
        ['code' => 'most_organized', 'category' => 'teacher', 'name' => 'Most Organized Teacher'],
        ['code' => 'best_visitor_followup', 'category' => 'teacher', 'name' => 'Best Visitor Follow-Up Teacher'],
        ['code' => 'excellence_teaching', 'category' => 'teacher', 'name' => 'Excellence in Teaching Award'],
    ];

    /** @var list<array{code: string, category: string, name: string}> */
    public const CLASS_AWARDS = [
        ['code' => 'best_class_year', 'category' => 'class', 'name' => 'Best Class of the Year'],
        ['code' => 'fastest_growing', 'category' => 'class', 'name' => 'Fastest Growing Class'],
        ['code' => 'most_disciplined', 'category' => 'class', 'name' => 'Most Disciplined Class'],
        ['code' => 'best_attendance_class', 'category' => 'class', 'name' => 'Best Attendance Class'],
        ['code' => 'best_offering_class', 'category' => 'class', 'name' => 'Best Offering Class'],
        ['code' => 'best_memory_class', 'category' => 'class', 'name' => 'Best Memory Verse Class'],
    ];

    public function __construct(
        private readonly ReportReadService $reportRead,
        private readonly AnalyticsReadService $analyticsRead,
    ) {}

    /** @return array{created: int, period_label: string} */
    public function generateRecommendations(string $periodType, int $adminId): array
    {
        $periodLabel = match ($periodType) {
            'monthly' => now()->format('F Y'),
            'quarterly' => 'Q'.ceil((int) now()->format('n') / 3).' '.now()->format('Y'),
            default => now()->format('Y'),
        };

        $scope = ['is_admin' => true, 'is_teacher' => false, 'class_ids' => []];
        $created = 0;
        $periodForAnalytics = $periodType === 'monthly' ? 'month' : 'year';

        $students = $this->reportRead->studentRankings($scope, 50);
        $pickStudent = static function (string $code) use ($students): ?array {
            return match ($code) {
                'most_improved' => $students[count($students) > 2 ? 2 : 0] ?? null,
                default => $students[0] ?? null,
            };
        };

        foreach (self::STUDENT_AWARDS as $def) {
            $stu = $pickStudent($def['code']);
            if ($stu && $this->insertAward(
                $def, 'student', (int) $stu['id'], (string) $stu['full_name'],
                (int) ($stu['class_id'] ?? 0), (string) ($stu['class_name'] ?? ''),
                (float) ($stu['overall_score'] ?? 0), $periodType, $periodLabel, $adminId,
            )) {
                $created++;
            }
        }

        $teachers = $this->analyticsRead->getTeacherAnalytics($scope, $periodForAnalytics)['rankings'];
        foreach (self::TEACHER_AWARDS as $i => $def) {
            $t = $teachers[$i % max(1, count($teachers))] ?? null;
            if ($t && $this->insertAward(
                $def, 'teacher', (int) $t['id'], (string) $t['full_name'], 0, (string) ($t['class_name'] ?? ''),
                (float) ($t['composite_score'] ?? 0), $periodType, $periodLabel, $adminId,
            )) {
                $created++;
            }
        }

        $classes = $this->analyticsRead->getClassAnalytics($scope, $periodForAnalytics)['rankings'];
        foreach (self::CLASS_AWARDS as $i => $def) {
            $c = $classes[$i % max(1, count($classes))] ?? null;
            if ($c && $this->insertAward(
                $def, 'class', (int) $c['id'], (string) $c['class_name'], (int) $c['id'],
                (string) $c['class_name'], (float) ($c['composite_score'] ?? 0), $periodType, $periodLabel, $adminId,
            )) {
                $created++;
            }
        }

        return ['created' => $created, 'period_label' => $periodLabel];
    }

    public function approveAward(int $id, int $adminId): void
    {
        SundaySchoolAward::query()->whereKey($id)->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function rejectAward(int $id, int $adminId, string $notes): void
    {
        SundaySchoolAward::query()->whereKey($id)->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_notes' => trim($notes),
        ]);
    }

    public function publishAward(int $id, int $adminId): void
    {
        $award = SundaySchoolAward::query()->findOrFail($id);
        $award->update([
            'status' => 'published',
            'approved_by' => $award->approved_by ?? $adminId,
            'approved_at' => $award->approved_at ?? now(),
        ]);
    }

    /** @param array{code: string, category: string, name: string} $def */
    private function insertAward(
        array $def,
        string $recipientType,
        int $recipientId,
        string $recipientName,
        int $classId,
        string $className,
        float $score,
        string $periodType,
        string $periodLabel,
        int $adminId,
    ): bool {
        if ($recipientType === 'student') {
            $student = DB::table('sunday_school_students as s')
                ->leftJoin('sunday_school_classes as c', 'c.id', '=', 's.class_id')
                ->where('s.id', $recipientId)
                ->select('s.full_name', 's.class_id', 'c.class_name')
                ->first();

            if (! $student) {
                return false;
            }

            $recipientName = (string) ($student->full_name ?? $recipientName);
            $classId = (int) ($student->class_id ?? 0);
            $className = (string) ($student->class_name ?? '');
        }

        $exists = SundaySchoolAward::query()
            ->where('award_code', $def['code'])
            ->where('period_label', $periodLabel)
            ->where('recipient_id', $recipientId)
            ->exists();

        if ($exists) {
            return false;
        }

        SundaySchoolAward::query()->create([
            'award_code' => $def['code'],
            'award_category' => $def['category'],
            'award_name' => $def['name'],
            'period_type' => $periodType,
            'period_label' => $periodLabel,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'recipient_name' => $recipientName,
            'class_id' => $classId ?: null,
            'class_name' => $className ?: null,
            'score' => $score,
            'achievement_summary' => 'Automatically recommended based on Sunday School performance analytics.',
            'status' => 'recommended',
            'recommended_at' => now(),
            'created_by' => $adminId,
        ]);

        return true;
    }
}
