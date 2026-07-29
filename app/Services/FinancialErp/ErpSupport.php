<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class ErpSupport
{
    public static function nextDocNo(string $prefix, string $table, string $column): string
    {
        $year = now()->format('Y');
        $like = $prefix.'-'.$year.'-%';
        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc('id')->value($column);
        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches)) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $seq);
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public static function logAudit(
        string $entityType,
        int $entityId,
        string $action,
        ?array $before,
        ?array $after,
        int $adminId,
        ?string $role = null,
    ): void {
        if (! DB::getSchemaBuilder()->hasTable('erp_audit_log')) {
            return;
        }

        try {
            DB::table('erp_audit_log')->insert([
                'entity_type' => $entityType,
                'entity_id' => $entityId > 0 ? $entityId : null,
                'action' => $action,
                'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                'user_id' => $adminId > 0 ? $adminId : null,
                'user_role' => $role,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255) ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never break business flow on audit failure.
        }
    }
}
