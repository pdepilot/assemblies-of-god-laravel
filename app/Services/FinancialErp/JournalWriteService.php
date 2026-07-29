<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class JournalWriteService
{
    public function __construct(
        private readonly JournalReadService $read,
    ) {}

    /**
     * @param array<string, mixed> $header
     * @param list<array<string, mixed>> $lines
     * @return array<string, mixed>
     */
    public function save(array $header, array $lines, int $adminId, bool $post = false, ?string $role = null): array
    {
        $id = (int) ($header['id'] ?? 0);
        $isNew = $id <= 0;
        $date = trim((string) ($header['journal_date'] ?? now()->toDateString()));
        $memo = trim((string) ($header['memo'] ?? ''));
        $reference = trim((string) ($header['reference'] ?? ''));

        $cleanLines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $i => $line) {
            if (! is_array($line)) {
                continue;
            }
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($accountId <= 0 || ($debit <= 0 && $credit <= 0)) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('A journal line cannot have both debit and credit.');
            }
            $cleanLines[] = [
                'line_no' => $i + 1,
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => trim((string) ($line['description'] ?? '')) ?: null,
            ];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (count($cleanLines) < 2) {
            throw new InvalidArgumentException('A journal needs at least two lines.');
        }
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('Journal is out of balance. Debits must equal credits.');
        }

        if ($id > 0) {
            $existing = $this->read->getJournal($id);
            if ($existing === null || ($existing['status'] ?? '') !== 'draft') {
                throw new InvalidArgumentException('Only draft journals can be edited.');
            }
            DB::table('erp_journals')->where('id', $id)->update([
                'journal_date' => $date,
                'reference' => $reference ?: null,
                'memo' => $memo ?: null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'updated_by' => $adminId ?: null,
                'updated_at' => now(),
            ]);
            DB::table('erp_journal_lines')->where('journal_id', $id)->delete();
        } else {
            $id = (int) DB::table('erp_journals')->insertGetId([
                'journal_no' => ErpSupport::nextDocNo('JE', 'erp_journals', 'journal_no'),
                'journal_date' => $date,
                'source_type' => (string) ($header['source_type'] ?? 'manual'),
                'reference' => $reference ?: null,
                'memo' => $memo ?: null,
                'status' => 'draft',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => $adminId ?: null,
                'updated_by' => $adminId ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($cleanLines as $line) {
            DB::table('erp_journal_lines')->insert([
                'journal_id' => $id,
                'line_no' => $line['line_no'],
                'account_id' => $line['account_id'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'description' => $line['description'],
            ]);
        }

        ErpSupport::logAudit('journal', $id, $isNew ? 'create' : 'save', null, ['lines' => count($cleanLines)], $adminId, $role);

        if ($post) {
            return $this->post($id, $adminId, $role);
        }

        $journal = $this->read->getJournal($id);
        if ($journal === null) {
            throw new InvalidArgumentException('Journal not found after save.');
        }

        return $journal;
    }

    /** @return array<string, mixed> */
    public function post(int $id, int $adminId, ?string $role = null): array
    {
        $journal = $this->read->getJournal($id);
        if ($journal === null) {
            throw new InvalidArgumentException('Journal not found.');
        }
        if (($journal['status'] ?? '') !== 'draft') {
            throw new InvalidArgumentException('Only draft journals can be posted.');
        }
        if (round((float) $journal['total_debit'], 2) !== round((float) $journal['total_credit'], 2)) {
            throw new InvalidArgumentException('Cannot post an unbalanced journal.');
        }

        DB::table('erp_journals')->where('id', $id)->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $adminId ?: null,
            'updated_by' => $adminId ?: null,
            'updated_at' => now(),
        ]);

        ErpSupport::logAudit('journal', $id, 'post', $journal, null, $adminId, $role);

        $updated = $this->read->getJournal($id);
        if ($updated === null) {
            throw new InvalidArgumentException('Journal not found after post.');
        }

        return $updated;
    }
}
