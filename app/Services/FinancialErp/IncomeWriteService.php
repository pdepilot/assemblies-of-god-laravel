<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class IncomeWriteService
{
    public function __construct(
        private readonly IncomeReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createCategory(array $data, int $adminId, ?string $role = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Category name is required (max 120 characters).');
        }

        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $code = preg_replace('/[^A-Z0-9_-]/', '', $code) ?? '';
        if ($code === '') {
            $code = $this->generateCategoryCode($name);
        }
        if (strlen($code) > 32) {
            throw new InvalidArgumentException('Category code must be 32 characters or fewer.');
        }
        if (DB::table('erp_income_categories')->where('code', $code)->exists()) {
            throw new InvalidArgumentException('A category with this code already exists.');
        }

        $accountId = ! empty($data['account_id']) ? (int) $data['account_id'] : null;
        if ($accountId === null) {
            $accountId = DB::table('erp_accounts')
                ->where('code', '4010')
                ->whereNull('deleted_at')
                ->value('id');
            $accountId = $accountId ? (int) $accountId : null;
        } elseif (! DB::table('erp_accounts')->where('id', $accountId)->whereNull('deleted_at')->exists()) {
            throw new InvalidArgumentException('Selected income account was not found.');
        }

        $id = (int) DB::table('erp_income_categories')->insertGetId([
            'code' => $code,
            'name' => $name,
            'account_id' => $accountId,
            'is_active' => true,
            'created_at' => now(),
        ]);

        ErpSupport::logAudit('income_category', $id, 'create', null, [
            'code' => $code,
            'name' => $name,
            'account_id' => $accountId,
        ], $adminId, $role);

        $category = $this->read->getCategory($id);
        if ($category === null) {
            throw new InvalidArgumentException('Category was created but could not be loaded.');
        }

        return $category;
    }

    private function generateCategoryCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?? '');
        $base = substr($base !== '' ? $base : 'CAT', 0, 6);
        $code = $base;
        $n = 1;
        while (DB::table('erp_income_categories')->where('code', $code)->exists()) {
            $suffix = (string) $n;
            $code = substr($base, 0, max(1, 8 - strlen($suffix))).$suffix;
            $n++;
            if ($n > 999) {
                throw new InvalidArgumentException('Unable to allocate a unique category code.');
            }
        }

        return $code;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId, ?string $role = null): array
    {
        $id = (int) ($data['id'] ?? 0);
        $isNew = $id <= 0;
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $categoryId = (int) ($data['category_id'] ?? 0);
        $newCategoryName = trim((string) ($data['new_category_name'] ?? ''));

        if ($amount <= 0) {
            throw new InvalidArgumentException('Valid amount is required.');
        }

        if ($categoryId <= 0 && $newCategoryName !== '') {
            $category = $this->findOrCreateCategoryByName($newCategoryName, $adminId, $role);
            $categoryId = (int) $category['id'];
        }

        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Select a category from the list, or enter a new category name.');
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

    /**
     * @return array<string, mixed>
     */
    private function findOrCreateCategoryByName(string $name, int $adminId, ?string $role): array
    {
        $name = trim($name);
        $existing = DB::table('erp_income_categories')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (! (bool) $existing->is_active) {
                DB::table('erp_income_categories')->where('id', $existing->id)->update(['is_active' => true]);
            }

            return (array) $existing + ['id' => (int) $existing->id, 'is_active' => true];
        }

        return $this->createCategory(['name' => $name], $adminId, $role);
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
