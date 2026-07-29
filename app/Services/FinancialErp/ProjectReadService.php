<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class ProjectReadService
{
    public const PROJECT_TYPES = ['building', 'missions', 'partnership', 'general', 'other'];

    public const STATUSES = ['planned', 'active', 'completed', 'on_hold', 'cancelled'];

    /** @return list<array<string, mixed>> */
    public function listProjects(): array
    {
        return DB::table('erp_projects')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getProject(int $id): ?array
    {
        $row = DB::table('erp_projects')->where('id', $id)->whereNull('deleted_at')->first();

        return $row ? (array) $row : null;
    }
}
