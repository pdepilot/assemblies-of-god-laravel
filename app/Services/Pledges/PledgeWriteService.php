<?php

namespace App\Services\Pledges;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PledgeWriteService
{
    public function __construct(
        private readonly PledgeReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createPledge(array $data): array
    {
        $name = trim((string) ($data['donor_name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Donor name is required.');
        }

        $pledged = round((float) ($data['pledged_amount'] ?? 0), 2);
        if ($pledged <= 0) {
            throw new InvalidArgumentException('Pledge amount must be greater than zero.');
        }

        $installmentCount = ! empty($data['installment_count']) ? (int) $data['installment_count'] : null;
        $installmentAmount = ! empty($data['installment_amount'])
            ? round((float) $data['installment_amount'], 2)
            : ($installmentCount ? round($pledged / $installmentCount, 2) : null);

        $startDate = trim((string) ($data['start_date'] ?? '')) ?: now()->toDateString();
        $endDate = trim((string) ($data['end_date'] ?? '')) ?: null;
        if (! $endDate && $installmentCount && $installmentCount > 0) {
            $endDate = now()->parse($startDate)->addMonths($installmentCount)->toDateString();
        }

        $now = now();
        $id = DB::table('pledges')->insertGetId([
            'donor_name' => $name,
            'donor_email' => trim((string) ($data['donor_email'] ?? '')) ?: null,
            'donor_phone' => trim((string) ($data['donor_phone'] ?? '')) ?: null,
            'category_id' => ! empty($data['category_id']) ? (int) $data['category_id'] : null,
            'pledged_amount' => $pledged,
            'amount_paid' => 0,
            'remaining_balance' => $pledged,
            'installment_amount' => $installmentAmount,
            'installment_count' => $installmentCount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_due_date' => $startDate,
            'status' => 'active',
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pledge = $this->read->getPledge($id);
        if ($pledge === null) {
            throw new InvalidArgumentException('Failed to create pledge.');
        }

        return $pledge;
    }

    /** @return array<string, mixed> */
    public function recordPayment(int $pledgeId, float $amount, ?int $donationId = null): array
    {
        $pledge = DB::table('pledges')->where('id', $pledgeId)->first();
        if (! $pledge) {
            throw new InvalidArgumentException('Pledge not found.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $paid = round((float) $pledge->amount_paid + $amount, 2);
        $remaining = max(0, round((float) $pledge->pledged_amount - $paid, 2));
        $status = $remaining <= 0 ? 'completed' : (string) $pledge->status;
        $nextDue = $remaining <= 0 ? null : $this->computeNextDueDate($pledge);

        DB::transaction(function () use ($pledgeId, $donationId, $amount, $paid, $remaining, $status, $nextDue) {
            DB::table('pledges')->where('id', $pledgeId)->update([
                'amount_paid' => $paid,
                'remaining_balance' => $remaining,
                'status' => $status,
                'next_due_date' => $nextDue,
                'updated_at' => now(),
            ]);

            DB::table('pledge_payments')->insert([
                'pledge_id' => $pledgeId,
                'donation_id' => $donationId,
                'amount' => $amount,
                'paid_at' => now()->toDateString(),
            ]);
        });

        $result = $this->read->getPledge($pledgeId);
        if ($result === null) {
            throw new InvalidArgumentException('Pledge not found after payment.');
        }

        return $result;
    }

    private function computeNextDueDate(object $pledge): ?string
    {
        $current = $pledge->next_due_date ?? $pledge->start_date;
        if (! $current) {
            return null;
        }

        return now()->parse((string) $current)->addMonth()->toDateString();
    }
}
