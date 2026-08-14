<?php

namespace App\Console\Commands;

use App\Services\Ministries\MinistryAgeTransferService;
use Illuminate\Console\Command;

final class TransferMinistryByAgeCommand extends Command
{
    protected $signature = 'ministries:age-transfer {--dry-run : Preview transfers without writing}';

    protected $description = 'Move Children (13+) to Teens and Teens (20+) to Youth Ministry by date of birth';

    public function handle(MinistryAgeTransferService $transfers): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $transfers->run('schedule', null, $dryRun);

        $this->info($dryRun ? 'Dry run — no changes written.' : 'Age transfers applied.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Children → Teens due', (string) ($result['counts']['children_to_teens'] ?? 0)],
                ['Teens → Youth due', (string) ($result['counts']['teens_to_youths'] ?? 0)],
                ['Missing DOB skipped', (string) ($result['skipped'] ?? 0)],
                ['Transferred', (string) ($result['transferred'] ?? 0)],
            ]
        );

        if (! empty($result['items'])) {
            $rows = [];
            foreach ($result['items'] as $item) {
                $rows[] = [
                    (string) ($item['full_name'] ?? ''),
                    (string) ($item['from_key'] ?? ''),
                    (string) ($item['to_key'] ?? ''),
                    (string) ($item['age'] ?? ''),
                ];
            }
            $this->table(['Name', 'From', 'To', 'Age'], $rows);
        }

        return self::SUCCESS;
    }
}
