<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AccountWriteService
{
    public function __construct(
        private readonly AccountReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId, ?string $role = null): array
    {
        $id = (int) ($data['id'] ?? 0);
        $code = trim((string) ($data['code'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $type = strtolower(trim((string) ($data['account_type'] ?? '')));

        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Account code and name are required.');
        }
        if (! in_array($type, AccountReadService::ACCOUNT_TYPES, true)) {
            throw new InvalidArgumentException('Invalid account type.');
        }

        $normal = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
        $payload = [
            'code' => $code,
            'name' => $name,
            'account_type' => $type,
            'subtype' => trim((string) ($data['subtype'] ?? '')) ?: null,
            'parent_id' => ! empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'normal_balance' => $normal,
            'is_postable' => ! empty($data['is_postable']),
            'is_bank' => ! empty($data['is_bank']),
            'is_cash' => ! empty($data['is_cash']),
            'opening_balance' => (float) ($data['opening_balance'] ?? 0),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'is_active' => ! isset($data['is_active']) || ! empty($data['is_active']),
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('erp_accounts')->where('id', $id)->whereNull('deleted_at')->update($payload);
            ErpSupport::logAudit('account', $id, 'update', null, $payload, $adminId, $role);
        } else {
            $payload['created_by'] = $adminId > 0 ? $adminId : null;
            $payload['created_at'] = now();
            $id = (int) DB::table('erp_accounts')->insertGetId($payload);
            ErpSupport::logAudit('account', $id, 'create', null, $payload, $adminId, $role);
        }

        $account = $this->read->getAccount($id);
        if ($account === null) {
            throw new InvalidArgumentException('Account not found after save.');
        }

        return $account;
    }

    public function softDelete(int $id, int $adminId, ?string $role = null): void
    {
        $before = $this->read->getAccount($id);
        if ($before === null) {
            throw new InvalidArgumentException('Account not found.');
        }

        DB::table('erp_accounts')->where('id', $id)->update([
            'deleted_at' => now(),
            'is_active' => false,
            'updated_by' => $adminId ?: null,
            'updated_at' => now(),
        ]);

        ErpSupport::logAudit('account', $id, 'soft_delete', $before, null, $adminId, $role);
    }
}
