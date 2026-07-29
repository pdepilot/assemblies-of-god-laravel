<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class ExpenseReadService
{
    /** @return list<array<string, mixed>> */
    public function listCategories(): array
    {
        return DB::table('erp_expense_categories')->where('is_active', true)->orderBy('name')->get()->map(fn ($r) => (array) $r)->all();
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int} */
    public function listExpenses(int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('erp_expenses as e')
            ->leftJoin('erp_expense_categories as c', 'c.id', '=', 'e.category_id')
            ->leftJoin('erp_vendors as v', 'v.id', '=', 'e.vendor_id')
            ->whereNull('e.deleted_at');

        $total = (clone $base)->count();
        $items = (clone $base)
            ->select(['e.*', 'c.name as category_name', 'v.name as vendor_name'])
            ->orderByDesc('e.expense_date')
            ->orderByDesc('e.id')
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
        ];
    }

    /** @return array<string, mixed>|null */
    public function getExpense(int $id): ?array
    {
        $row = DB::table('erp_expenses as e')
            ->leftJoin('erp_expense_categories as c', 'c.id', '=', 'e.category_id')
            ->leftJoin('erp_vendors as v', 'v.id', '=', 'e.vendor_id')
            ->where('e.id', $id)
            ->whereNull('e.deleted_at')
            ->select(['e.*', 'c.name as category_name', 'v.name as vendor_name'])
            ->first();

        return $row ? (array) $row : null;
    }
}
