<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveAutomationRuleRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\AutomationReadService;
use App\Services\CommunicationHub\AutomationWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class AutomationController
{
    public function __construct(
        private readonly AutomationReadService $read,
        private readonly AutomationWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.automation.index', [
            'rules' => $this->read->listRules(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageHub($this->admin());

        return view('communication-hub.automation.create', [
            'channels' => AutomationReadService::CHANNELS,
        ]);
    }

    public function store(SaveAutomationRuleRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.automation.index')
            ->with('status', 'Automation rule saved.');
    }

    public function edit(int $rule): View
    {
        $this->policy->requireManageHub($this->admin());
        $row = $this->read->getRule($rule);
        abort_if($row === null, 404);

        return view('communication-hub.automation.edit', [
            'rule' => $row,
            'channels' => AutomationReadService::CHANNELS,
        ]);
    }

    public function update(SaveAutomationRuleRequest $request, int $rule): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $data['id'] = $rule;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.automation.index')
            ->with('status', 'Automation rule updated.');
    }

    public function toggle(Request $request, int $rule): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $enabled = filter_var($request->input('is_enabled', false), FILTER_VALIDATE_BOOL);

        try {
            $this->write->setEnabled($rule, $enabled);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['toggle' => $e->getMessage()]);
        }

        return back()->with('status', $enabled ? 'Rule enabled.' : 'Rule disabled.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
