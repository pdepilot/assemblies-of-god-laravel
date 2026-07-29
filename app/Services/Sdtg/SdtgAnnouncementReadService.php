<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgAnnouncementReadService
{
    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return DB::table('sdtg_announcements')
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
