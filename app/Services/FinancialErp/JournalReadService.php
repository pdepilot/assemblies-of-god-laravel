<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;

final class JournalReadService
{
    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int} */
    public function listJournals(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('erp_journals')->whereNull('deleted_at');
        if ($status !== null && $status !== '') {
            $base->where('status', $status);
        }

        $total = (clone $base)->count();
        $items = (clone $base)
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
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
    public function getJournal(int $id): ?array
    {
        $row = DB::table('erp_journals')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $row) {
            return null;
        }

        $journal = (array) $row;
        $journal['lines'] = DB::table('erp_journal_lines as jl')
            ->join('erp_accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $id)
            ->orderBy('jl.line_no')
            ->select(['jl.*', 'a.code as account_code', 'a.name as account_name'])
            ->get()
            ->map(fn ($line) => (array) $line)
            ->all();

        return $journal;
    }
}
