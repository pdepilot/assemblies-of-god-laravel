<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;

final class HubNotificationReadService
{
    /** @return list<array<string, mixed>> */
    public function listForAdmin(int $adminId, bool $includeArchived = false): array
    {
        $query = DB::table('notification_center')
            ->where(function ($q) use ($adminId) {
                $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
            });

        if (! $includeArchived) {
            $query->where('is_archived', false);
        }

        return $query
            ->orderBy('is_read')
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
