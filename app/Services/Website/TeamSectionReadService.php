<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;

final class TeamSectionReadService
{
    /** @return list<array<string, mixed>> */
    public function listMembers(string $site = 'ag'): array
    {
        return DB::table('site_team_members')
            ->where('site', $site)
            ->orderBy('sort_order')
            ->orderBy('full_name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
