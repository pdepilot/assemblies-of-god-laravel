<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveProjectRequest;
use App\Models\Admin;
use App\Models\ErpProject;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\ProjectReadService;
use App\Services\FinancialErp\ProjectWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class ProjectsController
{
    public function __construct(
        private readonly ProjectReadService $read,
        private readonly ProjectWriteService $write,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);

        return view('financial-erp.projects.index', [
            'items' => $this->read->listProjects(),
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.projects.create', [
            'types' => ProjectReadService::PROJECT_TYPES,
            'statuses' => ProjectReadService::STATUSES,
        ]);
    }

    public function store(SaveProjectRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $project = $this->write->save($request->validated(), (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.projects.show', $project['id'])->with('status', 'Project created.');
    }

    public function show(ErpProject $project): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $data = $this->read->getProject((int) $project->id);
        abort_if($data === null, 404);

        return view('financial-erp.projects.show', ['project' => $data]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
