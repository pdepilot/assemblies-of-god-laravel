<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class AccountReadService
{
    public const ACCOUNT_TYPES = ['asset', 'liability', 'equity', 'income', 'expense'];

    /** @return list<array<string, mixed>> */
    public function listAccounts(?string $type = null): array
    {
        $query = DB::table('erp_accounts as a')
            ->leftJoin('erp_accounts as p', 'p.id', '=', 'a.parent_id')
            ->whereNull('a.deleted_at')
            ->where('a.is_active', true)
            ->select(['a.*', 'p.name as parent_name'])
            ->orderBy('a.code');

        if ($type !== null && $type !== '') {
            $query->where('a.account_type', $type);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return array<string, mixed>|null */
    public function getAccount(int $id): ?array
    {
        $row = DB::table('erp_accounts')->where('id', $id)->whereNull('deleted_at')->first();

        return $row ? (array) $row : null;
    }
}
