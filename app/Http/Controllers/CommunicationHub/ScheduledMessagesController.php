<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveScheduledMessageRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\CampaignReadService;
use App\Services\CommunicationHub\ScheduledMessageReadService;
use App\Services\CommunicationHub\ScheduledMessageWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class ScheduledMessagesController
{
    public function __construct(
        private readonly ScheduledMessageReadService $read,
        private readonly ScheduledMessageWriteService $write,
        private readonly CampaignReadService $campaigns,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.scheduled.index', [
            'items' => $this->read->listUpcoming(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageHub($this->admin());

        return view('communication-hub.scheduled.create', [
            'channels' => ScheduledMessageReadService::CHANNELS,
            'priorities' => ScheduledMessageReadService::PRIORITIES,
            'recurrences' => ScheduledMessageReadService::RECURRENCES,
            'audiences' => $this->campaigns->audienceOptions(),
        ]);
    }

    public function store(SaveScheduledMessageRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->save($request->validated() + ['status' => 'scheduled'], (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.scheduled.index')
            ->with('status', 'Scheduled message saved.');
    }

    public function edit(int $message): View
    {
        $this->policy->requireManageHub($this->admin());
        $row = $this->read->getMessage($message);
        abort_if($row === null, 404);

        return view('communication-hub.scheduled.edit', [
            'message' => $row,
            'channels' => ScheduledMessageReadService::CHANNELS,
            'priorities' => ScheduledMessageReadService::PRIORITIES,
            'recurrences' => ScheduledMessageReadService::RECURRENCES,
            'audiences' => $this->campaigns->audienceOptions(),
        ]);
    }

    public function update(SaveScheduledMessageRequest $request, int $message): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $data['id'] = $message;
        $data['status'] = $data['status'] ?? 'scheduled';

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.scheduled.index')
            ->with('status', 'Scheduled message updated.');
    }

    public function cancel(int $message): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->cancel($message);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('status', 'Scheduled message cancelled.');
    }

    public function destroy(int $message): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->delete($message);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.scheduled.index')
            ->with('status', 'Scheduled message deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
