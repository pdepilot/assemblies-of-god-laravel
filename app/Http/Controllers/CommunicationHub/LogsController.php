<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\CommunicationLogReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LogsController
{
    public function __construct(
        private readonly CommunicationLogReadService $read,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $logs = $this->read->listLogs(
            (string) $request->query('channel', ''),
            (string) $request->query('status', ''),
            max(1, (int) $request->query('page', 1)),
            CommunicationLogReadService::PER_PAGE,
        );

        return view('communication-hub.logs.index', [
            'logs' => $logs,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function show(Request $request, int $log): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $source = (string) $request->query('source', 'communication_logs');
        $row = $this->read->getLog($log, $source === 'email_history' ? 'email_history' : null);
        abort_if($row === null, 404);

        return view('communication-hub.logs.show', [
            'log' => $row,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
