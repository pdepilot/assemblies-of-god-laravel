<?php

namespace App\Services\SundaySchool;

use App\Services\Security\SecurityAuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AttendanceWriteService
{
    private const ATTENDANCE_STATUSES = ['present', 'absent', 'excused'];

    private const ARRIVAL_STATUSES = ['early', 'on_time', 'late', 'unknown'];

    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{attendance: int, offerings: int, memory_verses: int}
     */
    public function saveRegisterBatch(array $records, int $adminId, array $scope): array
    {
        $attendanceRows = [];
        foreach ($records as $row) {
            $attendanceRows[] = [
                'student_id' => (int) ($row['student_id'] ?? 0),
                'class_id' => (int) ($row['class_id'] ?? 0),
                'attendance_date' => (string) ($row['attendance_date'] ?? now()->toDateString()),
                'status' => (string) ($row['status'] ?? 'present'),
                'arrival_status' => (string) ($row['arrival_status'] ?? 'unknown'),
            ];
        }

        $attSaved = $this->saveAttendanceBatch($attendanceRows, $adminId, $scope);
        $offSaved = 0;
        $mvSaved = 0;

        foreach ($records as $row) {
            $studentId = (int) ($row['student_id'] ?? 0);
            $classId = (int) ($row['class_id'] ?? 0);
            $date = (string) ($row['attendance_date'] ?? now()->toDateString());

            if (! $this->classInScope($classId, $scope)) {
                continue;
            }

            $amount = round((float) ($row['offering_amount'] ?? 0), 2);
            if ($this->upsertDailyOffering($studentId, $classId, $date, $amount, $adminId)) {
                $offSaved++;
            }

            $memoryVerse = filter_var($row['memory_verse'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($this->upsertDailyMemoryVerse($studentId, $classId, $date, $memoryVerse, $adminId)) {
                $mvSaved++;
            }
        }

        if ($attSaved > 0) {
            $this->audit->log(
                'ss_attendance',
                'Sunday School attendance saved for '.$attSaved.' student'.($attSaved === 1 ? '' : 's').'.',
                $adminId,
                'info',
                ['attendance' => $attSaved, 'offerings' => $offSaved, 'memory_verses' => $mvSaved],
            );
        }

        return [
            'attendance' => $attSaved,
            'offerings' => $offSaved,
            'memory_verses' => $mvSaved,
        ];
    }

    public function voidStudentRegisterEntry(
        int $studentId,
        int $classId,
        string $date,
        string $reason,
        int $adminId,
        array $scope,
    ): void {
        if (! $this->classInScope($classId, $scope)) {
            throw new InvalidArgumentException('You do not have access to this class.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required to remove this register entry.');
        }
        if (strlen($reason) > 2000) {
            throw new InvalidArgumentException('Reason is too long (maximum 2000 characters).');
        }

        $meta = DB::table('sunday_school_students as s')
            ->join('sunday_school_classes as c', 'c.id', '=', 's.class_id')
            ->where('s.id', $studentId)
            ->where('s.class_id', $classId)
            ->select('s.full_name', 's.student_code', 'c.class_name')
            ->first();

        if (! $meta) {
            throw new InvalidArgumentException('Student not found in this class.');
        }

        $attendance = DB::table('sunday_school_attendance')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('attendance_date', $date)
            ->first();

        $offeringAmount = round((float) DB::table('sunday_school_offerings')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('offering_date', $date)
            ->sum('amount'), 2);

        $mvPassed = (int) DB::table('sunday_school_memory_verses')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('recitation_date', $date)
            ->whereIn('score', ['excellent', 'passed'])
            ->exists();

        $hasMvRecord = DB::table('sunday_school_memory_verses')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('recitation_date', $date)
            ->exists();

        if (! $attendance && $offeringAmount <= 0 && ! $hasMvRecord) {
            throw new InvalidArgumentException('No register entry found for this student on this date.');
        }

        $teacherId = ($scope['is_teacher'] ?? false) ? ($scope['teacher_id'] ?? null) : null;

        DB::transaction(function () use (
            $attendance,
            $studentId,
            $classId,
            $date,
            $meta,
            $offeringAmount,
            $mvPassed,
            $reason,
            $adminId,
            $teacherId,
        ) {
            DB::table('sunday_school_attendance_removals')->insert([
                'attendance_id' => $attendance?->id,
                'student_id' => $studentId,
                'class_id' => $classId,
                'student_name' => (string) $meta->full_name,
                'student_code' => $meta->student_code,
                'class_name' => (string) $meta->class_name,
                'attendance_date' => $date,
                'status' => $attendance?->status,
                'arrival_status' => $attendance?->arrival_status,
                'offering_amount' => $offeringAmount,
                'memory_verse_passed' => $mvPassed,
                'reason' => $reason,
                'removed_by' => $adminId,
                'teacher_id' => $teacherId,
                'removed_at' => now(),
            ]);

            DB::table('sunday_school_attendance')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->where('attendance_date', $date)
                ->delete();

            DB::table('sunday_school_offerings')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->where('offering_date', $date)
                ->delete();

            DB::table('sunday_school_memory_verses')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->where('recitation_date', $date)
                ->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    private function saveAttendanceBatch(array $records, int $adminId, array $scope): int
    {
        $saved = 0;
        $now = now();

        foreach ($records as $row) {
            $classId = (int) ($row['class_id'] ?? 0);
            if (! $this->classInScope($classId, $scope)) {
                continue;
            }

            $studentId = (int) ($row['student_id'] ?? 0);
            if ($studentId <= 0 || $classId <= 0) {
                continue;
            }

            $date = (string) ($row['attendance_date'] ?? $now->toDateString());
            $status = (string) ($row['status'] ?? 'present');
            $arrivalStatus = (string) ($row['arrival_status'] ?? 'unknown');

            DB::table('sunday_school_attendance')->upsert(
                [[
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'attendance_date' => $date,
                    'status' => in_array($status, self::ATTENDANCE_STATUSES, true) ? $status : 'present',
                    'arrival_status' => in_array($arrivalStatus, self::ARRIVAL_STATUSES, true) ? $arrivalStatus : 'unknown',
                    'arrival_time' => null,
                    'recorded_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['student_id', 'attendance_date'],
                ['class_id', 'status', 'arrival_status', 'arrival_time', 'recorded_by', 'updated_at'],
            );

            $saved++;
        }

        return $saved;
    }

    private function upsertDailyOffering(int $studentId, int $classId, string $date, float $amount, int $adminId): bool
    {
        DB::table('sunday_school_offerings')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('offering_date', $date)
            ->delete();

        if ($amount <= 0) {
            return false;
        }

        DB::table('sunday_school_offerings')->insert([
            'student_id' => $studentId,
            'class_id' => $classId,
            'offering_date' => $date,
            'amount' => $amount,
            'recorded_by' => $adminId,
            'created_at' => now(),
        ]);

        return true;
    }

    private function upsertDailyMemoryVerse(int $studentId, int $classId, string $date, bool $passed, int $adminId): bool
    {
        $verseRef = 'Weekly Memory Verse';
        $score = $passed ? 'passed' : 'not_recited';

        $existingId = DB::table('sunday_school_memory_verses')
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('recitation_date', $date)
            ->value('id');

        if ($existingId) {
            DB::table('sunday_school_memory_verses')
                ->where('id', $existingId)
                ->update([
                    'score' => $score,
                    'verse_reference' => $verseRef,
                    'recorded_by' => $adminId,
                ]);
        } else {
            DB::table('sunday_school_memory_verses')->insert([
                'student_id' => $studentId,
                'class_id' => $classId,
                'verse_reference' => $verseRef,
                'recitation_date' => $date,
                'score' => $score,
                'recorded_by' => $adminId,
                'created_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function classInScope(int $classId, array $scope): bool
    {
        if ($scope['is_admin'] ?? false) {
            return true;
        }

        return in_array($classId, array_map('intval', $scope['class_ids'] ?? []), true);
    }
}
