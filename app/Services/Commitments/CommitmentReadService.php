<?php

namespace App\Services\Commitments;

use Illuminate\Support\Facades\DB;

final class CommitmentReadService
{
    public const STATUSES = ['draft', 'active', 'completed', 'archived'];

    public const GIVER_STATUSES = ['active', 'completed', 'cancelled'];

    public const FREQUENCIES = ['weekly', 'monthly', 'quarterly', 'yearly', 'one_time'];

    /** @return list<array<string, mixed>> */
    public function listPrograms(string $status = ''): array
    {
        $query = DB::table('commitment_programs as cp')
            ->select([
                'cp.*',
                DB::raw('(SELECT COALESCE(SUM(committed_amount), 0) FROM commitment_givers cg WHERE cg.program_id = cp.id) as pledged_total'),
                DB::raw('(SELECT COALESCE(SUM(amount_paid), 0) FROM commitment_givers cg WHERE cg.program_id = cp.id) as paid_total'),
                DB::raw('(SELECT COUNT(*) FROM commitment_givers cg WHERE cg.program_id = cp.id) as giver_count'),
            ])
            ->orderByDesc('cp.created_at');

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $query->where('cp.status', $status);
        }

        return $query->get()->map(fn ($row) => $this->formatProgram((array) $row))->all();
    }

    /** @return array<string, mixed>|null */
    public function getProgram(int $id): ?array
    {
        $row = DB::table('commitment_programs as cp')
            ->select([
                'cp.*',
                DB::raw('(SELECT COALESCE(SUM(committed_amount), 0) FROM commitment_givers cg WHERE cg.program_id = cp.id) as pledged_total'),
                DB::raw('(SELECT COALESCE(SUM(amount_paid), 0) FROM commitment_givers cg WHERE cg.program_id = cp.id) as paid_total'),
                DB::raw('(SELECT COUNT(*) FROM commitment_givers cg WHERE cg.program_id = cp.id) as giver_count'),
            ])
            ->where('cp.id', $id)
            ->first();

        return $row ? $this->formatProgram((array) $row) : null;
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int} */
    public function listGivers(int $programId = 0, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('commitment_givers as cg')
            ->join('commitment_programs as cp', 'cp.id', '=', 'cg.program_id');

        if ($programId > 0) {
            $base->where('cg.program_id', $programId);
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->select(['cg.*', 'cp.name as program_name'])
            ->orderByDesc('cg.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'items' => $rows->map(fn ($row) => $this->formatGiver((array) $row))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatProgram(array $row): array
    {
        $target = (float) ($row['target_amount'] ?? 0);
        $paid = (float) ($row['paid_total'] ?? 0);

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'description' => $row['description'],
            'target_amount' => $target,
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => (string) ($row['status'] ?? 'draft'),
            'pledged_total' => (float) ($row['pledged_total'] ?? 0),
            'paid_total' => $paid,
            'giver_count' => (int) ($row['giver_count'] ?? 0),
            'progress_pct' => $target > 0 ? round(($paid / $target) * 100, 1) : 0.0,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatGiver(array $row): array
    {
        $committed = (float) ($row['committed_amount'] ?? 0);
        $paid = (float) ($row['amount_paid'] ?? 0);

        return [
            'id' => (int) $row['id'],
            'program_id' => (int) $row['program_id'],
            'program_name' => (string) ($row['program_name'] ?? ''),
            'donor_name' => (string) $row['donor_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'committed_amount' => $committed,
            'amount_paid' => $paid,
            'remaining' => max(0, $committed - $paid),
            'frequency' => (string) ($row['frequency'] ?? 'monthly'),
            'status' => (string) ($row['status'] ?? 'active'),
        ];
    }
}
