<?php

namespace App\Services\SundaySchool;

use Illuminate\Support\Facades\DB;

final class NotificationReadService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listNotifications(int $limit = 50): array
    {
        return DB::table('sunday_school_notifications')
            ->orderByDesc('created_at')
            ->limit(max(1, min(200, $limit)))
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }
}
