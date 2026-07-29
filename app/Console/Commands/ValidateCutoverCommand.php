<?php

namespace App\Console\Commands;

use App\Services\Analytics\CutoverValidationService;
use Illuminate\Console\Command;

final class ValidateCutoverCommand extends Command
{
    protected $signature = 'analytics:validate-cutover';

    protected $description = 'Run staging cutover readiness checks (DB, traffic beacon, reports storage)';

    public function handle(CutoverValidationService $validation): int
    {
        $this->info('Running cutover validation...');
        $this->newLine();

        $result = $validation->run();
        $rows = [];

        foreach ($result['checks'] as $check) {
            $rows[] = [
                $check['passed'] ? 'PASS' : 'FAIL',
                $check['label'],
                $check['detail'],
            ];
        }

        $this->table(['Status', 'Check', 'Detail'], $rows);
        $this->newLine();
        $this->line('Recommended legacy beacon URL: ' . $validation->recommendedBeaconUrl());
        $this->line('Copy config/cutover.local.php.example → config/cutover.local.php in the legacy site root.');

        if ($result['passed']) {
            $this->info('All cutover checks passed.');

            return self::SUCCESS;
        }

        $this->warn('Some checks failed. Review CUTOVER.md before production cutover.');

        return self::FAILURE;
    }
}
