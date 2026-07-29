<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Models\HubNotification;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\HubNotificationReadService;
use App\Services\CommunicationHub\HubNotificationWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class NotificationsController
{
    public function __construct(
        private readonly HubNotificationReadService $read,
        private readonly HubNotificationWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.notifications.index', [
            'items' => $this->read->listForAdmin((int) $admin->id),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function markRead(HubNotification $hubNotification): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $this->write->markRead((int) $hubNotification->id, (int) $admin->id);

        return back()->with('status', 'Notification marked as read.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
