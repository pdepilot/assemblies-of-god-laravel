<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\QueueNotificationRequest;
use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\NotificationReadService;
use App\Services\SundaySchool\NotificationWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class NotificationsController
{
    public function __construct(
        private readonly NotificationReadService $read,
        private readonly NotificationWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        return view('sunday-school.notifications.index', [
            'items' => $this->read->listNotifications(50),
        ]);
    }

    public function store(QueueNotificationRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $this->write->queueNotification($request->validated(), (int) $admin->id);

        return redirect()
            ->route('ss.notifications.index')
            ->with('status', 'Notification queued.');
    }

    public function sendPending(): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $sent = $this->write->sendPendingNotifications((int) $admin->id);

        return redirect()
            ->route('ss.notifications.index')
            ->with('status', "{$sent} pending notification(s) processed.");
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
