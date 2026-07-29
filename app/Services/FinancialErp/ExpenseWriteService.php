<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ExpenseWriteService
{
    public function __construct(
        private readonly ExpenseReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId, ?string $role = null): array
    {
        $id = (int) ($data['id'] ?? 0);
        $isNew = $id <= 0;
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $categoryId = (int) ($data['category_id'] ?? 0);

        if ($amount <= 0 || $categoryId <= 0) {
            throw new InvalidArgumentException('Valid amount and category are required.');
        }

        $approvalStatus = trim((string) ($data['approval_status'] ?? 'draft')) ?: 'draft';
        $fields = [
            'expense_date' => trim((string) ($data['expense_date'] ?? now()->toDateString())),
            'category_id' => $categoryId,
            'vendor_id' => ! empty($data['vendor_id']) ? (int) $data['vendor_id'] : null,
            'account_id' => ! empty($data['account_id']) ? (int) $data['account_id'] : null,
            'amount' => $amount,
            'payment_method' => trim((string) ($data['payment_method'] ?? 'cash')) ?: 'cash',
            'reference_no' => trim((string) ($data['reference_no'] ?? '')) ?: null,
            'project_id' => ! empty($data['project_id']) ? (int) $data['project_id'] : null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'payment_status' => trim((string) ($data['payment_status'] ?? 'unpaid')) ?: 'unpaid',
            'approval_status' => $approvalStatus,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('erp_expenses')->where('id', $id)->whereNull('deleted_at')->update($fields);
        } else {
            $fields['voucher_no'] = ErpSupport::nextDocNo('EXP', 'erp_expenses', 'voucher_no');
            $fields['created_by'] = $adminId ?: null;
            $fields['created_at'] = now();
            $id = (int) DB::table('erp_expenses')->insertGetId($fields);

            if (in_array($approvalStatus, ['draft', 'pending'], true)) {
                DB::table('erp_approvals')->insert([
                    'entity_type' => 'expense',
                    'entity_id' => $id,
                    'level_no' => 1,
                    'approver_role' => 'finance_manager',
                    'status' => 'pending',
                    'created_at' => now(),
                ]);
            }
        }

        ErpSupport::logAudit('expense', $id, $isNew ? 'create' : 'update', null, $fields, $adminId, $role);

        $expense = $this->read->getExpense($id);
        if ($expense === null) {
            throw new InvalidArgumentException('Expense not found.');
        }

        return $expense;
    }
}
