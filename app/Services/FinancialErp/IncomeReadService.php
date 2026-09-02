<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class IncomeReadService
{
    /** @return list<array<string, mixed>> */
    public function listCategories(bool $activeOnly = true): array
    {
        $query = DB::table('erp_income_categories')->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get()->map(fn ($r) => (array) $r)->all();
    }

    /** @return array<string, mixed>|null */
    public function getCategory(int $id): ?array
    {
        $row = DB::table('erp_income_categories')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function listIncomeAccounts(): array
    {
        return DB::table('erp_accounts')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('account_type', 'income')
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, total_amount: float} */
    public function listIncome(int $page = 1, int $perPage = 25, ?string $categoryCode = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('erp_income as i')
            ->leftJoin('erp_income_categories as c', 'c.id', '=', 'i.category_id')
            ->whereNull('i.deleted_at');

        if ($categoryCode !== null && $categoryCode !== '') {
            $base->where('c.code', strtoupper($categoryCode));
        }

        $total = (clone $base)->count();
        $totalAmount = (float) (clone $base)->sum('i.amount');

        $items = (clone $base)
            ->select(['i.*', 'c.name as category_name', 'c.code as category_code'])
            ->orderByDesc('i.income_date')
            ->orderByDesc('i.id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'total_amount' => $totalAmount,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getIncome(int $id): ?array
    {
        $row = DB::table('erp_income as i')
            ->leftJoin('erp_income_categories as c', 'c.id', '=', 'i.category_id')
            ->where('i.id', $id)
            ->whereNull('i.deleted_at')
            ->select(['i.*', 'c.name as category_name', 'c.code as category_code'])
            ->first();

        return $row ? (array) $row : null;
    }
}
