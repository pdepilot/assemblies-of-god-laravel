<?php

namespace App\Services\Donations;

use App\Services\Security\SecurityAuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DonationWriteService
{
    public function __construct(
        private readonly DonationReadService $read,
        private readonly SecurityAuditService $audit,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function recordManual(array $data, int $adminId): array
    {
        $validated = $this->validateInput($data, true);
        $category = $this->resolveCategory($validated);
        $code = $this->generateDonationCode();
        $now = now();

        $id = DB::table('donations')->insertGetId([
            'donation_code' => $code,
            'donor_name' => $validated['donor_name'],
            'donor_email' => $validated['donor_email'],
            'donor_phone' => $validated['donor_phone'],
            'donor_location' => $validated['donor_location'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'category' => $category['legacy_category'],
            'category_id' => $category['id'],
            'fund_scope' => $validated['fund_scope'],
            'payment_method' => $validated['payment_method'],
            'payment_provider' => 'manual',
            'payment_status' => 'successful',
            'is_anonymous' => $validated['is_anonymous'],
            'donation_date' => $validated['donation_date'],
            'notes' => $validated['notes'],
            'recorded_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $donation = $this->read->getDonation($id);
        if ($donation === null) {
            throw new InvalidArgumentException('Failed to record donation.');
        }

        $this->audit->log(
            'donation_recorded',
            'Donation '.$code.' of '.$validated['currency'].' '.number_format((float) $validated['amount'], 2).' was recorded.',
            $adminId,
            'info',
            ['donation_id' => $id, 'donation_code' => $code],
        );

        return $donation;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function validateInput(array $data, bool $manual): array
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Donation amount must be greater than zero.');
        }

        $scope = (string) ($data['fund_scope'] ?? 'church');
        if (! in_array($scope, DonationReadService::FUND_SCOPES, true)) {
            $scope = 'church';
        }

        $categorySlug = trim((string) ($data['category_slug'] ?? $data['category'] ?? ''));
        if ($categorySlug === 'sdtg' || str_contains(strtolower($categorySlug), 'sdtg')) {
            $scope = 'sdtg';
        }

        $donationDate = trim((string) ($data['donation_date'] ?? ''));
        if ($donationDate === '') {
            $donationDate = now()->toDateString();
        }

        $donorName = trim((string) ($data['donor_name'] ?? ''));
        if ($donorName === '') {
            $donorName = 'Anonymous';
        }

        return [
            'donor_name' => $donorName,
            'donor_email' => trim((string) ($data['donor_email'] ?? '')) ?: null,
            'donor_phone' => trim((string) ($data['donor_phone'] ?? '')) ?: null,
            'donor_location' => trim((string) ($data['donor_location'] ?? '')) ?: null,
            'amount' => $amount,
            'currency' => strtoupper(trim((string) ($data['currency'] ?? 'NGN'))) ?: 'NGN',
            'category_id' => ! empty($data['category_id']) ? (int) $data['category_id'] : null,
            'category_slug' => $categorySlug,
            'fund_scope' => $scope,
            'payment_method' => $manual ? (trim((string) ($data['payment_method'] ?? 'cash')) ?: 'cash') : 'online',
            'is_anonymous' => ! empty($data['is_anonymous']),
            'donation_date' => $donationDate,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ];
    }

    /** @param array<string, mixed> $validated @return array{id: int, legacy_category: string, name: string} */
    private function resolveCategory(array $validated): array
    {
        if (! empty($validated['category_id'])) {
            $row = DB::table('donation_categories')->where('id', (int) $validated['category_id'])->first();
            if ($row) {
                return [
                    'id' => (int) $row->id,
                    'legacy_category' => (string) $row->legacy_category,
                    'name' => (string) $row->name,
                ];
            }
        }

        $slug = $validated['category_slug'];
        if ($slug !== '') {
            $row = DB::table('donation_categories')->where('slug', $slug)->first();
            if ($row) {
                return [
                    'id' => (int) $row->id,
                    'legacy_category' => (string) $row->legacy_category,
                    'name' => (string) $row->name,
                ];
            }
        }

        $legacy = $slug !== '' ? $slug : 'offering';
        $row = DB::table('donation_categories')->where('legacy_category', $legacy)->first();
        if ($row) {
            return [
                'id' => (int) $row->id,
                'legacy_category' => (string) $row->legacy_category,
                'name' => (string) $row->name,
            ];
        }

        return ['id' => 0, 'legacy_category' => $legacy, 'name' => ucfirst(str_replace('_', ' ', $legacy))];
    }

    private function generateDonationCode(): string
    {
        $next = ((int) DB::table('donations')->max('id')) + 1;

        return 'D'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
