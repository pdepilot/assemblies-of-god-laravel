<?php

namespace App\Services\Donations;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DonationReadService
{
    public const FUND_SCOPES = ['church'];

    public const PAYMENT_METHODS = ['cash', 'bank_transfer', 'cheque', 'pos', 'online'];

    public static function applyCountableFilter(Builder $query, string $prefix = ''): Builder
    {
        $col = $prefix !== '' ? $prefix.'.' : '';

        return $query->where(function (Builder $q) use ($col) {
            $q->where("{$col}payment_status", 'successful')
                ->orWhere("{$col}payment_provider", 'manual')
                ->orWhereNull("{$col}payment_status");
        });
    }

    /** @return list<array<string, mixed>> */
    public function listCategories(bool $activeOnly = true): array
    {
        $query = DB::table('donation_categories')->orderBy('sort_order')->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    /** @return array<string, mixed> */
    public function getStats(string $scope = 'church'): array
    {
        $scope = in_array($scope, self::FUND_SCOPES, true) ? $scope : 'church';
        $monthStart = now()->startOfMonth()->toDateString();
        $prevStart = now()->subMonth()->startOfMonth()->toDateString();
        $prevEnd = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        $current = $this->sumBetween($scope, $monthStart, $today);
        $previous = $this->sumBetween($scope, $prevStart, $prevEnd);
        $transactions = $this->countBetween($scope, $monthStart, $today);

        return [
            'total_month' => $current,
            'previous_month' => $previous,
            'trend' => $this->percentChange($current, $previous),
            'transactions' => $transactions,
            'online_month' => $this->sumOnlineBetween($scope, $monthStart, $today),
            'manual_month' => $this->sumManualBetween($scope, $monthStart, $today),
        ];
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int} */
    public function listDonations(string $scope, string $category, string $query, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('donations as d')
            ->leftJoin('donation_categories as dc', 'dc.id', '=', 'd.category_id');

        self::applyCountableFilter($base, 'd');

        if ($scope !== '' && in_array($scope, self::FUND_SCOPES, true)) {
            $base->where('d.fund_scope', $scope);
        }

        if ($category !== '') {
            $base->where(function (Builder $q) use ($category) {
                $q->where('d.category', $category)->orWhere('dc.slug', $category);
            });
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function (Builder $q) use ($like) {
                $q->where('d.donor_name', 'like', $like)
                    ->orWhere('d.donation_code', 'like', $like)
                    ->orWhere('d.payment_reference', 'like', $like)
                    ->orWhere('d.notes', 'like', $like);
            });
        }

        $total = (clone $base)->count();

        $rows = (clone $base)
            ->select([
                'd.*',
                'dc.name as category_label_new',
                'dc.slug as category_slug',
            ])
            ->orderByDesc('d.donation_date')
            ->orderByDesc('d.id')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = $rows->map(fn ($row) => $this->formatDonation((array) $row))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** @return array<string, mixed> */
    public function getDonation(int $id): ?array
    {
        $row = DB::table('donations as d')
            ->leftJoin('donation_categories as dc', 'dc.id', '=', 'd.category_id')
            ->where('d.id', $id)
            ->select(['d.*', 'dc.name as category_label_new', 'dc.slug as category_slug'])
            ->first();

        return $row ? $this->formatDonation((array) $row) : null;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatDonation(array $row): array
    {
        $categoryLabel = $row['category_label_new']
            ?? ucfirst(str_replace('_', ' ', (string) ($row['category'] ?? 'offering')));

        return [
            'id' => (int) $row['id'],
            'donation_code' => (string) $row['donation_code'],
            'donor_name' => (string) $row['donor_name'],
            'donor_email' => $row['donor_email'],
            'donor_phone' => $row['donor_phone'],
            'amount' => (float) $row['amount'],
            'currency' => (string) ($row['currency'] ?? 'NGN'),
            'category' => (string) ($row['category'] ?? 'offering'),
            'category_label' => $categoryLabel,
            'category_slug' => (string) ($row['category_slug'] ?? $row['category'] ?? ''),
            'fund_scope' => (string) ($row['fund_scope'] ?? 'church'),
            'payment_method' => $row['payment_method'],
            'payment_provider' => $row['payment_provider'],
            'payment_status' => $row['payment_status'],
            'donation_date' => (string) $row['donation_date'],
            'notes' => $row['notes'],
            'is_anonymous' => (bool) ($row['is_anonymous'] ?? false),
        ];
    }

    private function sumBetween(string $scope, string $start, string $end): float
    {
        $query = DB::table('donations')
            ->where('fund_scope', $scope)
            ->where('donation_date', '>=', $start)
            ->where('donation_date', '<=', $end);

        self::applyCountableFilter($query);

        return (float) $query->sum('amount');
    }

    private function countBetween(string $scope, string $start, string $end): int
    {
        $query = DB::table('donations')
            ->where('fund_scope', $scope)
            ->where('donation_date', '>=', $start)
            ->where('donation_date', '<=', $end);

        self::applyCountableFilter($query);

        return (int) $query->count();
    }

    private function sumOnlineBetween(string $scope, string $start, string $end): float
    {
        return (float) DB::table('donations')
            ->where('fund_scope', $scope)
            ->where('donation_date', '>=', $start)
            ->where('donation_date', '<=', $end)
            ->whereIn('payment_provider', ['paystack', 'flutterwave'])
            ->where('payment_status', 'successful')
            ->sum('amount');
    }

    private function sumManualBetween(string $scope, string $start, string $end): float
    {
        return (float) DB::table('donations')
            ->where('fund_scope', $scope)
            ->where('donation_date', '>=', $start)
            ->where('donation_date', '<=', $end)
            ->where('payment_provider', 'manual')
            ->sum('amount');
    }

    private function percentChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
