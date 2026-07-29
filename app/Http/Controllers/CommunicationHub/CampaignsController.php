<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveCampaignRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\CampaignReadService;
use App\Services\CommunicationHub\CampaignWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class CampaignsController
{
    public function __construct(
        private readonly CampaignReadService $read,
        private readonly CampaignWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.campaigns.index', [
            'campaigns' => $this->read->listCampaigns(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageHub($this->admin());

        return view('communication-hub.campaigns.create', [
            'types' => CampaignReadService::TYPES,
            'statuses' => ['draft', 'scheduled', 'archived'],
            'audiences' => $this->read->audienceOptions(),
        ]);
    }

    public function store(SaveCampaignRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.campaigns.index')
            ->with('status', 'Campaign saved.');
    }

    public function edit(int $campaign): View
    {
        $this->policy->requireManageHub($this->admin());
        $row = $this->read->getCampaign($campaign);
        abort_if($row === null, 404);

        return view('communication-hub.campaigns.edit', [
            'campaign' => $row,
            'types' => CampaignReadService::TYPES,
            'statuses' => ['draft', 'scheduled', 'archived', 'cancelled'],
            'audiences' => $this->read->audienceOptions(),
        ]);
    }

    public function update(SaveCampaignRequest $request, int $campaign): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $data['id'] = $campaign;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.campaigns.index')
            ->with('status', 'Campaign updated.');
    }

    public function destroy(int $campaign): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->delete($campaign);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.campaigns.index')
            ->with('status', 'Campaign deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
