<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class StudentWriteService
{
    private const STUDENT_STATUSES = ['active', 'archived', 'graduated'];
    private const CHURCH_STATUSES = ['full_member', 'baptized', 'visitor', 'unbaptized'];

    public function __construct(
        private readonly SundaySchoolScopeService $scope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data, int $adminId, array $accessScope): array
    {
        $id = (int) ($data['id'] ?? 0);
        $payload = $this->validateStudent($data);

        $this->scope->assertClassInScope($accessScope, $payload['class_id']);

        if ($id > 0) {
            $student = SundaySchoolStudent::query()->findOrFail($id);
            $this->assertStudentInScope($accessScope, $student);
            $payload['parent_id'] = $this->upsertParent($payload);
            $student->update($payload);

            return $student->fresh()->toArray();
        }

        $payload['student_code'] = $this->nextCode('SS-STU', 'sunday_school_students', 'student_code');
        $payload['parent_id'] = $this->upsertParent($payload);

        $student = SundaySchoolStudent::query()->create($payload);

        return $student->toArray();
    }

    public function transferToClass(int $studentId, int $toClassId, int $adminId, array $accessScope, ?string $notes = null): void
    {
        $student = SundaySchoolStudent::query()->findOrFail($studentId);
        $this->assertStudentInScope($accessScope, $student);
        $this->scope->assertClassInScope($accessScope, $toClassId);

        $fromClassId = (int) ($student->class_id ?? 0);

        $student->update(['class_id' => $toClassId]);
        $this->recordPromotion($studentId, $fromClassId ?: null, $toClassId, 'transfer', $adminId, $notes);
    }

    public function archive(int $studentId, int $adminId, array $accessScope): void
    {
        $student = SundaySchoolStudent::query()->findOrFail($studentId);
        $this->assertStudentInScope($accessScope, $student);

        $student->update(['status' => 'archived']);
    }

    public function delete(int $studentId, int $adminId): void
    {
        SundaySchoolStudent::query()->whereKey($studentId)->delete();
    }

    private function assertStudentInScope(array $scope, SundaySchoolStudent $student): void
    {
        if ($scope['is_admin'] ?? false) {
            return;
        }

        $classId = (int) ($student->class_id ?? 0);
        $classIds = array_map('intval', $scope['class_ids'] ?? []);

        if ($classId === 0 || ! in_array($classId, $classIds, true)) {
            throw new InvalidArgumentException('You can only manage students in your assigned classes.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateStudent(array $data): array
    {
        $name = trim((string) ($data['full_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Student name is required.');
        }

        $membership = $this->normalizeChurchStatus($data['membership_status'] ?? 'unbaptized') ?? 'unbaptized';
        $baptism = $this->baptismFromChurchStatus($membership);

        if (isset($data['baptism_status']) && in_array($data['baptism_status'], ['not_baptized', 'baptized', 'unknown'], true)) {
            if (($data['membership_status'] ?? '') === '') {
                $baptism = (string) $data['baptism_status'];
            }
        }

        $maritalStatus = strtolower(trim((string) ($data['marital_status'] ?? 'unspecified')));
        $allowedMarital = ['single', 'married', 'divorced', 'widowed', 'separated', 'unspecified'];
        if (! in_array($maritalStatus, $allowedMarital, true)) {
            $maritalStatus = 'unspecified';
        }

        $weddingDate = null;
        if ($maritalStatus === 'married') {
            $weddingRaw = trim((string) ($data['wedding_date'] ?? ''));
            if ($weddingRaw === '') {
                throw new InvalidArgumentException('Wedding date is required when marital status is Married.');
            }
            if ($weddingRaw > date('Y-m-d')) {
                throw new InvalidArgumentException('Wedding date must be a valid past or today date (YYYY-MM-DD).');
            }
            $dobRaw = trim((string) ($data['date_of_birth'] ?? ''));
            if ($dobRaw !== '' && $weddingRaw < $dobRaw) {
                throw new InvalidArgumentException('Wedding date cannot be before date of birth.');
            }
            $weddingDate = $weddingRaw;
        }

        $status = (string) ($data['status'] ?? 'active');

        return [
            'full_name' => $name,
            'date_of_birth' => ($data['date_of_birth'] ?? '') ?: null,
            'gender' => in_array($data['gender'] ?? '', ['male', 'female', 'unspecified'], true) ? $data['gender'] : 'unspecified',
            'marital_status' => $maritalStatus,
            'wedding_date' => $weddingDate,
            'parent_name' => trim((string) ($data['parent_name'] ?? '')) ?: null,
            'parent_phone' => trim((string) ($data['parent_phone'] ?? '')) ?: null,
            'parent_email' => trim((string) ($data['parent_email'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'class_id' => ($data['class_id'] ?? '') !== '' ? (int) $data['class_id'] : null,
            'department' => trim((string) ($data['department'] ?? '')) ?: 'Sunday School',
            'date_joined' => ($data['date_joined'] ?? '') ?: date('Y-m-d'),
            'baptism_status' => $baptism,
            'membership_status' => $membership,
            'status' => in_array($status, self::STUDENT_STATUSES, true) ? $status : 'active',
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function upsertParent(array $payload): ?int
    {
        if (! Schema::hasTable('sunday_school_parents')) {
            return null;
        }

        $phone = trim((string) ($payload['parent_phone'] ?? ''));
        $name = trim((string) ($payload['parent_name'] ?? ''));

        if ($name === '' && $phone === '') {
            return null;
        }

        if ($name === '') {
            $name = 'Parent/Guardian';
        }

        if ($phone !== '') {
            $existing = DB::table('sunday_school_parents')->where('phone', $phone)->value('id');
            if ($existing) {
                DB::table('sunday_school_parents')->where('id', $existing)->update([
                    'full_name' => $name,
                    'email' => $payload['parent_email'] ?? null,
                    'address' => $payload['address'] ?? null,
                ]);

                return (int) $existing;
            }
        }

        return (int) DB::table('sunday_school_parents')->insertGetId([
            'full_name' => $name,
            'email' => $payload['parent_email'] ?? null,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $payload['address'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function nextCode(string $prefix, string $table, string $column): string
    {
        $last = DB::table($table)->orderByDesc('id')->value($column);
        $num = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $num = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $num, 5, '0', STR_PAD_LEFT);
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

    private function baptismFromChurchStatus(string $churchStatus): string
    {
        return match ($churchStatus) {
            'baptized', 'full_member' => 'baptized',
            'unbaptized' => 'not_baptized',
            default => 'unknown',
        };
    }
}
