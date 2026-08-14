<?php

namespace App\Http\Controllers\Ministries;

use App\Models\Admin;
use App\Policies\MinistryPolicy;
use App\Services\Ministries\MinistryAgeTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MinistryAgeTransferController
{
    public function __construct(
        private readonly MinistryAgeTransferService $transfers,
        private readonly MinistryPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewMinistries($admin);

        $preview = $this->transfers->preview();

        return view('ministries.age-transfers.index', [
            'preview' => $preview,
            'canManage' => $this->policy->manageMinistries($admin),
            'recent' => \App\Models\MinistryAgeTransfer::query()
                ->orderByDesc('transferred_at')
                ->limit(25)
                ->get()
                ->map(static fn ($row) => $row->toArray())
                ->all(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMinistries($admin);

        $dryRun = $request->boolean('dry_run');
        $result = $this->transfers->run('manual', (int) $admin->id, $dryRun);

        if ($dryRun) {
            return redirect()
                ->route('ministries.age-transfers.index')
                ->with('status', sprintf(
                    'Preview: %d due (Children→Teens %d, Teens→Youth %d). Missing DOB: %d.',
                    count($result['items'] ?? []),
                    (int) ($result['counts']['children_to_teens'] ?? 0),
                    (int) ($result['counts']['teens_to_youths'] ?? 0),
                    (int) ($result['skipped'] ?? 0),
                ));
        }

        return redirect()
            ->route('ministries.age-transfers.index')
            ->with('status', sprintf(
                'Transferred %d people. Skipped %d missing date of birth.',
                (int) ($result['transferred'] ?? 0),
                (int) ($result['skipped'] ?? 0),
            ));
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
