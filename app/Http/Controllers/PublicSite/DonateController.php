<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Donations\DonationReadService;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class DonateController extends Controller
{
    public function __construct(
        private readonly PublicHomepageReadService $homepage,
        private readonly DonationReadService $donations,
        private readonly SeoReadService $seo,
        private readonly WebsitePagesReadService $pages,
    ) {}

    public function show(): View
    {
        $payload = $this->homepage->payload();
        $payments = $this->paymentPublicConfig();
        $categories = array_values(array_filter(
            $this->donations->listCategories(true),
            static fn (array $row): bool => (bool) ($row['is_public'] ?? true)
        ));
        $featuredCampaign = $this->featuredCampaign();
        $page = $this->pages->getPage('donate');
        $seo = $this->seo->forKey('donate', url('/donate'));
        $brand = (string) config('identity.public.short_name', 'AGC-Ikenegbu');
        if (trim((string) ($seo['title'] ?? '')) === '') {
            $seo['title'] = 'Give & Donate | Partner with '.$brand.' Ministries';
        }
        if (trim((string) ($seo['meta_description'] ?? '')) === '') {
            $seo['meta_description'] = 'Give through tithes, offerings, charity, and building fund gifts. Secure, transparent church giving for Nigeria and international partners.';
        }

        return view('public.donate', [
            'church' => $payload['church'],
            'page' => $page,
            'categories' => $categories,
            'featuredCampaign' => $featuredCampaign,
            'paystackEnabled' => $payments['paystack_enabled'],
            'flutterwaveEnabled' => $payments['flutterwave_enabled'],
            'paystackKey' => $payments['paystack_public_key'],
            'flutterwaveKey' => $payments['flutterwave_public_key'],
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'testimonySourcePage' => 'donate',
            'allowAds' => false,
            'bodyClass' => 'donate-page',
            'bodyDataAttrs' => [
                'csrf' => csrf_token(),
                'paystack-key' => $payments['paystack_public_key'],
                'flutterwave-key' => $payments['flutterwave_public_key'],
                'fund-scope' => 'church',
                'categories' => json_encode($categories, JSON_UNESCAPED_UNICODE) ?: '[]',
            ],
        ]);
    }

    /** @return array{paystack_enabled: bool, flutterwave_enabled: bool, paystack_public_key: string, flutterwave_public_key: string} */
    private function paymentPublicConfig(): array
    {
        $stored = [];
        if (Schema::hasTable('platform_setting_groups')) {
            $raw = DB::table('platform_setting_groups')->where('group_key', 'payments')->value('settings');
            if (is_string($raw) && $raw !== '') {
                try {
                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    $stored = is_array($decoded) ? $decoded : [];
                } catch (\Throwable) {
                    $stored = [];
                }
            }
        }

        $paystackKey = trim((string) ($stored['paystack_public_key'] ?? env('PAYSTACK_PUBLIC_KEY', '')));
        $flutterwaveKey = trim((string) ($stored['flutterwave_public_key'] ?? env('FLUTTERWAVE_PUBLIC_KEY', '')));
        $paystackEnabled = filter_var($stored['paystack_enabled'] ?? ($paystackKey !== ''), FILTER_VALIDATE_BOOL)
            && $paystackKey !== '';
        $flutterwaveEnabled = filter_var($stored['flutterwave_enabled'] ?? ($flutterwaveKey !== ''), FILTER_VALIDATE_BOOL)
            && $flutterwaveKey !== '';

        return [
            'paystack_enabled' => $paystackEnabled,
            'flutterwave_enabled' => $flutterwaveEnabled,
            'paystack_public_key' => $paystackEnabled ? $paystackKey : '',
            'flutterwave_public_key' => $flutterwaveEnabled ? $flutterwaveKey : '',
        ];
    }

    /** @return array<string, mixed>|null */
    private function featuredCampaign(): ?array
    {
        if (! Schema::hasTable('fundraising_campaigns')) {
            return null;
        }

        $query = DB::table('fundraising_campaigns')
            ->where('is_active', true)
            ->where('is_public_visible', true)
            ->where(function ($q): void {
                $q->whereNull('fund_scope')->orWhere('fund_scope', 'church');
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        $row = $query->first();
        if ($row === null) {
            return null;
        }

        $target = (float) ($row->target_amount ?? 0);
        $raised = 0.0;
        if (Schema::hasTable('donations') && Schema::hasColumn('donations', 'campaign_id')) {
            $sumQuery = DB::table('donations')->where('campaign_id', (int) $row->id);
            DonationReadService::applyCountableFilter($sumQuery);
            $raised = (float) $sumQuery->sum('amount');
        }

        $pct = $target > 0 ? round(($raised / $target) * 100, 1) : 0.0;
        $daysRemaining = null;
        if (! empty($row->end_date)) {
            try {
                $end = \Carbon\Carbon::parse((string) $row->end_date)->startOfDay();
                $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($end, false));
            } catch (\Throwable) {
                $daysRemaining = null;
            }
        }

        return [
            'has_campaign' => true,
            'id' => (int) $row->id,
            'title' => (string) ($row->title ?? ''),
            'target_amount' => $target,
            'raised_amount' => $raised,
            'remaining_amount' => max(0, $target - $raised),
            'progress_pct' => $pct,
            'donor_count' => (int) ($row->donor_count ?? 0),
            'end_date' => $row->end_date ? (string) $row->end_date : null,
            'days_remaining' => $daysRemaining,
        ];
    }
}
