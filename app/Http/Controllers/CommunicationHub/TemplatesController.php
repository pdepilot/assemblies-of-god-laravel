<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveTemplateRequest;
use App\Models\Admin;
use App\Models\CommunicationTemplate;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\TemplateReadService;
use App\Services\CommunicationHub\TemplateWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class TemplatesController
{
    public function __construct(
        private readonly TemplateReadService $read,
        private readonly TemplateWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.templates.index', [
            'items' => $this->read->listTemplates(
                (string) $request->query('channel', ''),
                (string) $request->query('status', ''),
            ),
            'channels' => TemplateReadService::CHANNELS,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageHub($this->admin());

        return view('communication-hub.templates.create', [
            'categories' => $this->read->listCategories(),
            'channels' => TemplateReadService::CHANNELS,
        ]);
    }

    public function store(SaveTemplateRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('communication-hub.templates.index')->with('status', 'Template saved.');
    }

    public function show(CommunicationTemplate $template): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);
        $row = $this->read->getTemplate((int) $template->id);
        abort_if($row === null, 404);

        return view('communication-hub.templates.show', [
            'template' => $row,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function edit(CommunicationTemplate $template): View
    {
        $this->policy->requireManageHub($this->admin());
        $row = $this->read->getTemplate((int) $template->id);
        abort_if($row === null, 404);

        return view('communication-hub.templates.edit', [
            'template' => $row,
            'categories' => $this->read->listCategories(),
            'channels' => TemplateReadService::CHANNELS,
        ]);
    }

    public function update(SaveTemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $data['id'] = (int) $template->id;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('communication-hub.templates.show', $template)->with('status', 'Template updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
