<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class VendorReadService
{
    /** @return list<array<string, mixed>> */
    public function listVendors(): array
    {
        return DB::table('erp_vendors')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getVendor(int $id): ?array
    {
        $row = DB::table('erp_vendors')->where('id', $id)->whereNull('deleted_at')->first();

        return $row ? (array) $row : null;
    }
}
