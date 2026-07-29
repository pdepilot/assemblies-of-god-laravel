<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;

final class HubNotificationWriteService
{
    public function markRead(int $id, int $adminId): void
    {
        DB::table('notification_center')
            ->where('id', $id)
            ->where(function ($q) use ($adminId) {
                $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
