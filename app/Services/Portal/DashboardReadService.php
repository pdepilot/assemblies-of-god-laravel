<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DashboardReadService
{
    /** @return array<string, mixed> */
    public function summary(): array
    {
        return [
            'members' => $this->count('members'),
            'visitors' => $this->count('visitors'),
            'events' => $this->countWhere('events', 'status', 'published'),
            'donations_month' => $this->donationsThisMonth(),
            'ss_students' => $this->count('sunday_school_students'),
            'site_sessions_today' => $this->sessionsToday(),
        ];
    }

    private function count(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function countWhere(string $table, string $column, string $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return $this->count($table);
        }

        return (int) DB::table($table)->where($column, $value)->count();
    }

    private function donationsThisMonth(): float
    {
        if (! Schema::hasTable('donations')) {
            return 0.0;
        }

        $amountCol = Schema::hasColumn('donations', 'amount') ? 'amount' : null;
        if ($amountCol === null) {
            return 0.0;
        }

        $query = DB::table('donations')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if (Schema::hasColumn('donations', 'status')) {
            $query->whereIn('status', ['completed', 'success', 'paid', 'confirmed']);
        }

        return (float) $query->sum($amountCol);
    }

    private function sessionsToday(): int
    {
        if (! Schema::hasTable('site_sessions')) {
            return 0;
        }

        return (int) DB::table('site_sessions')
            ->whereDate('started_at', now()->toDateString())
            ->count();
    }
}
