<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class ErpAuditReadService
{
    /** @return array{items: list<array<string, mixed>>, count: int} */
    public function listAuditTrail(int $limit = 100, ?string $entityType = null): array
    {
        $limit = max(1, min(200, $limit));

        $query = DB::table('erp_audit_log as a')
            ->leftJoin('admins as ad', 'ad.id', '=', 'a.user_id')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->select([
                'a.*',
                DB::raw("COALESCE(ad.full_name, ad.email, CONCAT('User #', a.user_id)) as user_name"),
            ]);

        if ($entityType !== null && $entityType !== '') {
            $query->where('a.entity_type', $entityType);
        }

        $items = $query->get()->map(fn ($row) => (array) $row)->all();

        return [
            'items' => $items,
            'count' => count($items),
        ];
    }
}
