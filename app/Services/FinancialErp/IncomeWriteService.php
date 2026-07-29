<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class IncomeWriteService
{
    public function __construct(
        private readonly IncomeReadService $read,
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

        $fields = [
            'income_date' => trim((string) ($data['income_date'] ?? now()->toDateString())),
            'category_id' => $categoryId,
            'account_id' => ! empty($data['account_id']) ? (int) $data['account_id'] : null,
            'amount' => $amount,
            'payment_method' => trim((string) ($data['payment_method'] ?? 'cash')) ?: 'cash',
            'reference_no' => trim((string) ($data['reference_no'] ?? '')) ?: null,
            'member_name' => trim((string) ($data['member_name'] ?? '')) ?: null,
            'project_id' => ! empty($data['project_id']) ? (int) $data['project_id'] : null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'approval_status' => trim((string) ($data['approval_status'] ?? 'draft')) ?: 'draft',
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('erp_income')->where('id', $id)->whereNull('deleted_at')->update($fields);
        } else {
            $fields['receipt_no'] = ErpSupport::nextDocNo('RCV', 'erp_income', 'receipt_no');
            $fields['created_by'] = $adminId ?: null;
            $fields['created_at'] = now();
            $id = (int) DB::table('erp_income')->insertGetId($fields);
        }

        ErpSupport::logAudit('income', $id, $isNew ? 'create' : 'update', null, $fields, $adminId, $role);

        $income = $this->read->getIncome($id);
        if ($income === null) {
            throw new InvalidArgumentException('Income record not found.');
        }

        $this->syncToReceipt($income, $adminId, $role);

        return $income;
    }

    /** @param array<string, mixed> $income */
    private function syncToReceipt(array $income, int $adminId, ?string $role): void
    {
        $incomeId = (int) ($income['id'] ?? 0);
        if ($incomeId < 1) {
            return;
        }

        try {
            $existingId = DB::table('erp_receipts')->where('income_id', $incomeId)->value('id');
            $payload = [
                'receipt_no' => (string) ($income['receipt_no'] ?? ErpSupport::nextDocNo('RCV', 'erp_receipts', 'receipt_no')),
                'receipt_date' => (string) ($income['income_date'] ?? now()->toDateString()),
                'payer_name' => trim((string) ($income['member_name'] ?? '')) ?: 'Church member / donor',
                'amount' => (float) ($income['amount'] ?? 0),
                'payment_method' => trim((string) ($income['payment_method'] ?? 'cash')) ?: 'cash',
                'reference_no' => trim((string) ($income['reference_no'] ?? '')) ?: null,
                'notes' => trim((string) ($income['notes'] ?? '')) ?: null,
                'income_id' => $incomeId,
            ];

            if ($existingId) {
                DB::table('erp_receipts')->where('id', $existingId)->update($payload);
            } else {
                $payload['created_by'] = $adminId ?: null;
                $payload['created_at'] = now();
                $rid = (int) DB::table('erp_receipts')->insertGetId($payload);
                ErpSupport::logAudit('receipt', $rid, 'create', null, $payload, $adminId, $role);
            }
        } catch (\Throwable) {
        }
    }
}
