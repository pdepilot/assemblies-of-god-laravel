<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class VendorWriteService
{
    public function __construct(
        private readonly VendorReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId, ?string $role = null): array
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Vendor name is required.');
        }

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = ErpSupport::nextDocNo('VND', 'erp_vendors', 'code');
        }

        $payload = [
            'code' => $code,
            'name' => $name,
            'contact_person' => trim((string) ($data['contact_person'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'is_active' => ! isset($data['is_active']) || ! empty($data['is_active']),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('erp_vendors')->where('id', $id)->whereNull('deleted_at')->update($payload);
            ErpSupport::logAudit('vendor', $id, 'update', null, $payload, $adminId, $role);
        } else {
            $payload['created_by'] = $adminId ?: null;
            $payload['created_at'] = now();
            $id = (int) DB::table('erp_vendors')->insertGetId($payload);
            ErpSupport::logAudit('vendor', $id, 'create', null, $payload, $adminId, $role);
        }

        $vendor = $this->read->getVendor($id);
        if ($vendor === null) {
            throw new InvalidArgumentException('Vendor not found after save.');
        }

        return $vendor;
    }
}
