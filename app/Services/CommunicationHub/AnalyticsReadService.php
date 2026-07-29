<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AnalyticsReadService
{
    /**
     * @return array{
     *     kpis: array{
     *         open_rate: float,
     *         click_rate: float,
     *         bounce_rate: float,
     *         delivery_success_rate: float,
     *         failed_messages: int
     *     },
     *     monthly_trends: list<array{month: string, email: int, sms: int}>,
     *     top_templates: list<array{slug: string, uses: int}>
     * }
     */
    public function getAnalytics(): array
    {
        return [
            'kpis' => $this->kpis(),
            'monthly_trends' => $this->monthlyTrends(),
            'top_templates' => $this->topTemplates(),
        ];
    }

    /**
     * @return array{
     *     open_rate: float,
     *     click_rate: float,
     *     bounce_rate: float,
     *     delivery_success_rate: float,
     *     failed_messages: int
     * }
     */
    private function kpis(): array
    {
        $total = 0;
        $opened = 0;
        $clicked = 0;
        $delivered = 0;
        $failedAllTime = 0;
        $failedEmailToday = 0;

        if (Schema::hasTable('email_history')) {
            $total = (int) DB::table('email_history')->count();
            $opened = (int) DB::table('email_history')->where('opened', 1)->count();
            $clicked = (int) DB::table('email_history')->where('clicked', 1)->count();
            $delivered = (int) DB::table('email_history')
                ->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])
                ->count();
            $failedAllTime = (int) DB::table('email_history')->where('status', 'failed')->count();
            $failedEmailToday = (int) DB::table('email_history')
                ->where('status', 'failed')
                ->whereDate('created_at', today())
                ->count();
        }

        $failedSmsToday = 0;
        if (Schema::hasTable('sms_logs')) {
            $failedSmsToday = (int) DB::table('sms_logs')
                ->where('status', 'failed')
                ->whereDate('created_at', today())
                ->count();
        }

        $deliveryRate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0.0;
        $openRate = $delivered > 0 ? round(($opened / $delivered) * 100, 1) : 0.0;
        $clickRate = $delivered > 0 ? round(($clicked / $delivered) * 100, 1) : 0.0;
        $bounceRate = max(0, round(100 - $deliveryRate, 1));

        return [
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'bounce_rate' => $bounceRate,
            'delivery_success_rate' => $deliveryRate,
            'failed_messages' => $failedEmailToday + $failedSmsToday + $failedAllTime,
        ];
    }

    /** @return list<array{month: string, email: int, sms: int}> */
    private function monthlyTrends(): array
    {
        $out = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $month = $start->format('Y-m');

            $email = 0;
            if (Schema::hasTable('email_history')) {
                $email = (int) DB::table('email_history')
                    ->whereBetween('created_at', [$start, $end])
                    ->count();
            }

            $sms = 0;
            if (Schema::hasTable('sms_logs')) {
                $sms = (int) DB::table('sms_logs')
                    ->whereBetween('created_at', [$start, $end])
                    ->count();
            }

            $out[] = [
                'month' => $month,
                'email' => $email,
                'sms' => $sms,
            ];
        }

        return $out;
    }

    /** @return list<array{slug: string, uses: int}> */
    private function topTemplates(): array
    {
        if (! Schema::hasTable('email_history')) {
            return [];
        }

        return DB::table('email_history')
            ->select('template_slug as slug', DB::raw('COUNT(*) as uses'))
            ->whereNotNull('template_slug')
            ->where('template_slug', '!=', '')
            ->groupBy('template_slug')
            ->orderByDesc('uses')
            ->limit(10)
            ->get()
            ->map(static fn ($row): array => [
                'slug' => (string) $row->slug,
                'uses' => (int) $row->uses,
            ])
            ->all();
    }
}
