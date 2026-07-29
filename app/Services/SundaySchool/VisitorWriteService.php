<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolVisitor;
use InvalidArgumentException;

final class VisitorWriteService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveVisitor(array $data, int $adminId, array $accessScope): array
    {
        $id = (int) ($data['id'] ?? 0);
        $classId = ($data['class_id'] ?? '') !== '' ? (int) $data['class_id'] : null;

        if ($classId && ($accessScope['is_teacher'] ?? false)) {
            $allowed = array_map('intval', $accessScope['class_ids'] ?? []);
            if (! in_array($classId, $allowed, true)) {
                throw new InvalidArgumentException('You do not have access to this class.');
            }
        }

        $payload = [
            'visitor_name' => trim((string) ($data['visitor_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'invited_by' => trim((string) ($data['invited_by'] ?? '')) ?: null,
            'class_id' => $classId,
            'visit_date' => (string) ($data['visit_date'] ?? now()->toDateString()),
            'follow_up_status' => in_array($data['follow_up_status'] ?? '', ['pending', 'contacted', 'converted', 'closed'], true)
                ? $data['follow_up_status']
                : 'pending',
            'follow_up_notes' => trim((string) ($data['follow_up_notes'] ?? '')) ?: null,
        ];

        if ($payload['visitor_name'] === '') {
            throw new InvalidArgumentException('Visitor name is required.');
        }

        if ($id > 0) {
            $visitor = SundaySchoolVisitor::query()->findOrFail($id);
            $visitor->update($payload);
        } else {
            $payload['created_at'] = now();
            $visitor = SundaySchoolVisitor::query()->create($payload);
        }

        $row = SundaySchoolVisitor::query()
            ->leftJoin('sunday_school_classes as c', 'c.id', '=', 'sunday_school_visitors.class_id')
            ->where('sunday_school_visitors.id', $visitor->id)
            ->select('sunday_school_visitors.*', 'c.class_name')
            ->first();

        return $row ? (array) $row->toArray() : $visitor->toArray();
    }
}
