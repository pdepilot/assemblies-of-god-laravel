<?php

namespace App\Services\Commitments;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CommitmentWriteService
{
    public function __construct(
        private readonly CommitmentReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createProgram(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Commitment name is required.');
        }

        $now = now();
        $id = DB::table('commitment_programs')->insertGetId([
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'target_amount' => (float) ($data['target_amount'] ?? 0),
            'start_date' => $this->nullableDate($data['start_date'] ?? null),
            'end_date' => $this->nullableDate($data['end_date'] ?? null),
            'status' => $this->normalizeStatus($data['status'] ?? 'draft'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $program = $this->read->getProgram($id);
        if ($program === null) {
            throw new InvalidArgumentException('Failed to create commitment program.');
        }

        return $program;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createGiver(array $data): array
    {
        $programId = (int) ($data['program_id'] ?? 0);
        $donorName = trim((string) ($data['donor_name'] ?? ''));
        if ($programId <= 0 || $donorName === '') {
            throw new InvalidArgumentException('Program and donor name are required.');
        }

        if ($this->read->getProgram($programId) === null) {
            throw new InvalidArgumentException('Commitment program not found.');
        }

        $committed = round((float) ($data['committed_amount'] ?? 0), 2);
        $paid = round((float) ($data['amount_paid'] ?? 0), 2);
        $now = now();

        $id = DB::table('commitment_givers')->insertGetId([
            'program_id' => $programId,
            'donor_name' => $donorName,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'committed_amount' => $committed,
            'amount_paid' => $paid,
            'frequency' => $this->normalizeFrequency($data['frequency'] ?? 'monthly'),
            'status' => $paid >= $committed && $committed > 0 ? 'completed' : 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->getGiver($id);
    }

    /** @return array<string, mixed> */
    public function recordGiverPayment(int $giverId, float $amount): array
    {
        $giver = DB::table('commitment_givers')->where('id', $giverId)->first();
        if (! $giver) {
            throw new InvalidArgumentException('Commitment giver not found.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $paid = round((float) $giver->amount_paid + $amount, 2);
        $committed = (float) $giver->committed_amount;
        $status = $paid >= $committed && $committed > 0 ? 'completed' : 'active';

        DB::table('commitment_givers')->where('id', $giverId)->update([
            'amount_paid' => $paid,
            'status' => $status,
            'updated_at' => now(),
        ]);

        return $this->getGiver($giverId);
    }

    /** @return array<string, mixed> */
    private function getGiver(int $id): array
    {
        $row = DB::table('commitment_givers as cg')
            ->join('commitment_programs as cp', 'cp.id', '=', 'cg.program_id')
            ->where('cg.id', $id)
            ->select(['cg.*', 'cp.name as program_name'])
            ->first();

        if (! $row) {
            throw new InvalidArgumentException('Commitment giver not found.');
        }

        $committed = (float) $row->committed_amount;
        $paid = (float) $row->amount_paid;

        return [
            'id' => (int) $row->id,
            'program_id' => (int) $row->program_id,
            'program_name' => (string) $row->program_name,
            'donor_name' => (string) $row->donor_name,
            'email' => $row->email,
            'phone' => $row->phone,
            'committed_amount' => $committed,
            'amount_paid' => $paid,
            'remaining' => max(0, $committed - $paid),
            'frequency' => (string) $row->frequency,
            'status' => (string) $row->status,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, CommitmentReadService::STATUSES, true) ? $status : 'draft';
    }

    private function normalizeFrequency(string $frequency): string
    {
        return in_array($frequency, CommitmentReadService::FREQUENCIES, true) ? $frequency : 'monthly';
    }

    private function nullableDate(mixed $value): ?string
    {
        $date = trim((string) ($value ?? ''));

        return $date !== '' ? $date : null;
    }
}
