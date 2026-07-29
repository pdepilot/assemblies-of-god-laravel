<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\QueueReadService;
use App\Services\CommunicationHub\QueueWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class QueueController
{
    public function __construct(
        private readonly QueueReadService $read,
        private readonly QueueWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $overview = $this->read->overview();

        return view('communication-hub.queue.index', [
            'overview' => $overview,
            'jobs' => $this->read->recentJobs(),
            'canManage' => $this->policy->manageHub($admin),
            'hasFailed' => ($overview['email_failed'] + $overview['sms_failed']) > 0,
        ]);
    }

    public function process(): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $result = $this->write->process(50);

        return redirect()
            ->route('communication-hub.queue.index')
            ->with(
                'status',
                sprintf(
                    'Processed queues — email sent: %d, SMS sent: %d, email failed: %d, SMS failed: %d.',
                    $result['email_sent'],
                    $result['sms_sent'],
                    $result['email_failed'],
                    $result['sms_failed']
                )
            );
    }

    public function destroyFailed(): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $result = $this->write->deleteFailed();

        return redirect()
            ->route('communication-hub.queue.index')
            ->with(
                'status',
                sprintf(
                    'Deleted failed queue jobs — email: %d, SMS: %d.',
                    $result['email_deleted'],
                    $result['sms_deleted']
                )
            );
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
