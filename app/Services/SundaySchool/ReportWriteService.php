<?php

namespace App\Services\SundaySchool;

use InvalidArgumentException;

final class ReportWriteService
{
    public function __construct(
        private readonly StudentReadService $studentRead,
        private readonly TeacherReadService $teacherRead,
        private readonly OfferingReadService $offeringRead,
        private readonly AttendanceReadService $attendanceRead,
        private readonly ReportExportService $export,
    ) {}

    /**
     * @return array{content: string, filename: string}
     */
    public function buildExport(string $type, array $accessScope, int $classId, string $from, string $to): array
    {
        $date = now()->toDateString();

        return match ($type) {
            'students' => $this->exportStudents($accessScope, $classId, $date),
            'attendance' => $this->exportAttendance($accessScope, $classId, $to, $date),
            'offerings' => $this->exportOfferings($accessScope, $classId, $from, $to, $date),
            'teachers' => $this->exportTeachers($accessScope, $date),
            default => throw new InvalidArgumentException('Unknown export type.'),
        };
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function exportStudents(array $accessScope, int $classId, string $date): array
    {
        $result = $this->studentRead->listStudents($accessScope, '', $classId, '', 1, 5000);
        $rows = array_map(static fn ($r) => [
            'student_code' => $r['student_code'],
            'full_name' => $r['full_name'],
            'class_name' => $r['class_name'] ?? '',
            'age' => $r['age'] ?? '',
            'parent_name' => $r['parent_name'] ?? '',
            'parent_phone' => $r['parent_phone'] ?? '',
            'status' => $r['status'],
        ], $result['items']);

        $headers = ['student_code', 'full_name', 'class_name', 'age', 'parent_name', 'parent_phone', 'status'];

        return [
            'content' => $this->export->toCsv($rows, $headers),
            'filename' => "ss-students-{$date}.csv",
        ];
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function exportAttendance(array $accessScope, int $classId, string $date, string $fileDate): array
    {
        if ($classId <= 0) {
            throw new InvalidArgumentException('Select a class to export attendance.');
        }

        $sheet = $this->attendanceRead->getSheet($classId, $date, $accessScope);
        $rows = array_map(static fn ($r) => [
            'student_code' => $r['student_code'] ?? '',
            'full_name' => $r['full_name'],
            'status' => $r['status'] ?? '',
            'arrival_status' => $r['arrival_status'] ?? '',
        ], $sheet);

        $headers = ['student_code', 'full_name', 'status', 'arrival_status'];

        return [
            'content' => $this->export->toCsv($rows, $headers),
            'filename' => "ss-attendance-{$fileDate}.csv",
        ];
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function exportOfferings(array $accessScope, int $classId, string $from, string $to, string $date): array
    {
        $result = $this->offeringRead->listOfferings($accessScope, $classId, $from, $to);
        $rows = array_map(static fn ($r) => [
            'offering_date' => $r['offering_date'],
            'student_name' => $r['student_name'],
            'class_name' => $r['class_name'],
            'amount' => $r['amount'],
        ], $result['items']);

        $headers = ['offering_date', 'student_name', 'class_name', 'amount'];

        return [
            'content' => $this->export->toCsv($rows, $headers),
            'filename' => "ss-offerings-{$date}.csv",
        ];
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function exportTeachers(array $accessScope, string $date): array
    {
        if (! ($accessScope['is_admin'] ?? false)) {
            throw new InvalidArgumentException('Access denied.');
        }

        $result = $this->teacherRead->listTeachers('', '', 1, 500);
        $rows = array_map(static fn ($r) => [
            'teacher_code' => $r['teacher_code'],
            'full_name' => $r['full_name'],
            'email' => $r['email'] ?? '',
            'phone' => $r['phone'] ?? '',
            'class_name' => $r['class_name'] ?? '',
            'status' => $r['status'],
        ], $result['items']);

        $headers = ['teacher_code', 'full_name', 'email', 'phone', 'class_name', 'status'];

        return [
            'content' => $this->export->toCsv($rows, $headers),
            'filename' => "ss-teachers-{$date}.csv",
        ];
    }
}
