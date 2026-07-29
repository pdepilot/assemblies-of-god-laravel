<?php

namespace App\Services\Pledges;

use Illuminate\Support\Facades\DB;

final class PledgeReadService
{
    public const STATUSES = ['active', 'completed', 'cancelled', 'overdue'];

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int} */
    public function listPledges(string $status, string $query, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('pledges as p')
            ->leftJoin('donation_categories as dc', 'dc.id', '=', 'p.category_id');

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $base->where('p.status', $status);
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('p.donor_name', 'like', $like)
                    ->orWhere('p.donor_email', 'like', $like);
            });
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->select(['p.*', 'dc.name as category_name'])
            ->orderByDesc('p.created_at')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'items' => $rows->map(fn ($row) => $this->formatPledge((array) $row))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getPledge(int $id): ?array
    {
        $row = DB::table('pledges as p')
            ->leftJoin('donation_categories as dc', 'dc.id', '=', 'p.category_id')
            ->where('p.id', $id)
            ->select(['p.*', 'dc.name as category_name'])
            ->first();

        return $row ? $this->formatPledge((array) $row) : null;
    }

    /** @return list<array<string, mixed>> */
    public function listPayments(int $pledgeId): array
    {
        return DB::table('pledge_payments')
            ->where('pledge_id', $pledgeId)
            ->orderByDesc('paid_at')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'amount' => (float) $row->amount,
                'paid_at' => (string) $row->paid_at,
                'donation_id' => $row->donation_id,
            ])
            ->all();
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatPledge(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'donor_name' => (string) $row['donor_name'],
            'donor_email' => $row['donor_email'],
            'donor_phone' => $row['donor_phone'],
            'category_name' => $row['category_name'] ?? null,
            'pledged_amount' => (float) $row['pledged_amount'],
            'amount_paid' => (float) $row['amount_paid'],
            'remaining_balance' => (float) $row['remaining_balance'],
            'installment_amount' => $row['installment_amount'] !== null ? (float) $row['installment_amount'] : null,
            'installment_count' => $row['installment_count'],
            'start_date' => (string) $row['start_date'],
            'end_date' => $row['end_date'],
            'next_due_date' => $row['next_due_date'],
            'status' => (string) $row['status'],
            'notes' => $row['notes'],
        ];
    }
}
