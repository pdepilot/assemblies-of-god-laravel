<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ActivityWriteService
{
    public function __construct(
        private readonly ActivityReadService $read,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function save(array $items, int $adminId): array
    {
        if (! Schema::hasTable('church_activities')) {
            throw new RuntimeException('Activities table is not available.');
        }

        $normalized = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $row = $this->read->normalize($item);
            if ($row['title'] === '' && $row['description'] === '') {
                continue;
            }
            $row['sort_order'] = $index + 1;
            $normalized[] = $row;
        }

        DB::transaction(function () use ($normalized, $adminId): void {
            $keepIds = [];

            foreach ($normalized as $row) {
                $id = (int) ($row['id'] ?? 0);
                $payload = [
                    'title' => $row['title'] !== '' ? $row['title'] : 'Activity',
                    'description' => $row['description'] !== '' ? $row['description'] : null,
                    'icon_class' => $row['icon_class'],
                    'meeting_schedule' => $row['meeting_schedule'],
                    'read_more_url' => $row['read_more_url'] !== '' ? $row['read_more_url'] : null,
                    'sort_order' => $row['sort_order'],
                    'is_published' => $row['is_published'] ? 1 : 0,
                    'updated_by' => $adminId > 0 ? $adminId : null,
                    'updated_at' => now(),
                ];

                $exists = $id > 0 && DB::table('church_activities')->where('id', $id)->exists();
                if ($exists) {
                    DB::table('church_activities')->where('id', $id)->update($payload);
                    $keepIds[] = $id;
                    continue;
                }

                $keepIds[] = (int) DB::table('church_activities')->insertGetId($payload + [
                    'activity_code' => $this->generateCode(),
                    'leader_name' => '',
                    'created_by' => $adminId > 0 ? $adminId : null,
                    'created_at' => now(),
                ]);
            }

            $query = DB::table('church_activities');
            if ($keepIds !== []) {
                $query->whereNotIn('id', $keepIds);
            }
            $query->delete();
        });

        return $this->read->published();
    }

    private function generateCode(): string
    {
        do {
            $code = 'A'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (DB::table('church_activities')->where('activity_code', $code)->exists());

        return $code;
    }
}
