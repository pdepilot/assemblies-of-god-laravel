<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolOffering;
use InvalidArgumentException;

final class OfferingWriteService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveOffering(array $data, int $adminId, array $scope): array
    {
        $classId = (int) ($data['class_id'] ?? 0);
        if (! ($scope['is_admin'] ?? false) && ! in_array($classId, array_map('intval', $scope['class_ids'] ?? []), true)) {
            throw new InvalidArgumentException('You do not have access to this class.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount < 0) {
            throw new InvalidArgumentException('Offering amount cannot be negative.');
        }

        $offering = SundaySchoolOffering::query()->create([
            'student_id' => (int) ($data['student_id'] ?? 0),
            'class_id' => $classId,
            'offering_date' => (string) ($data['offering_date'] ?? now()->toDateString()),
            'amount' => $amount,
            'recorded_by' => $adminId,
            'created_at' => now(),
        ]);

        return $offering->toArray();
    }
}
