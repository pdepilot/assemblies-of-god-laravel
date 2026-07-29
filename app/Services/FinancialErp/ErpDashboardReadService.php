<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class ErpDashboardReadService
{
    /** @return array<string, mixed> */
    public function getStats(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'today_income' => $this->sumIncome($today, $today),
            'today_expenses' => $this->sumExpenses($today, $today),
            'month_income' => $this->sumIncome($monthStart, $today),
            'month_expenses' => $this->sumExpenses($monthStart, $today),
            'cash_balance' => $this->sumBankBalance(true),
            'bank_balance' => $this->sumBankBalance(false),
            'pending_approvals' => $this->countPendingApprovals(),
            'account_count' => (int) DB::table('erp_accounts')->whereNull('deleted_at')->count(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getSettings(): array
    {
        return DB::table('erp_settings')->orderBy('setting_key')->get()->map(fn ($row) => [
            'key' => (string) $row->setting_key,
            'value' => (string) ($row->setting_value ?? ''),
        ])->all();
    }

    private function sumIncome(string $from, string $to): float
    {
        return (float) DB::table('erp_income')
            ->whereNull('deleted_at')
            ->whereIn('approval_status', ['approved', 'posted', 'pending', 'draft'])
            ->whereBetween('income_date', [$from, $to])
            ->sum('amount');
    }

    private function sumExpenses(string $from, string $to): float
    {
        return (float) DB::table('erp_expenses')
            ->whereNull('deleted_at')
            ->whereIn('approval_status', ['approved', 'posted', 'pending', 'draft'])
            ->whereBetween('expense_date', [$from, $to])
            ->sum('amount');
    }

    private function sumBankBalance(bool $cashOnly): float
    {
        $query = DB::table('erp_bank_accounts')->where('is_active', true);
        $query->where('is_cash', $cashOnly);

        return (float) $query->sum('current_balance');
    }

    private function countPendingApprovals(): int
    {
        return (int) DB::table('erp_approvals')->where('status', 'pending')->count();
    }
}
