<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SaveActivitiesRequest;
use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\Website\ActivityReadService;
use App\Services\Website\ActivityWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ActivitiesController
{
    public function __construct(
        private readonly ActivityReadService $read,
        private readonly ActivityWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function edit(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        return view('website.activities.edit', [
            'activities' => $this->read->editorSlots(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function update(SaveActivitiesRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        $this->write->save($request->validated('activities') ?? [], (int) $admin->id);

        return redirect()
            ->route('website.activities.edit')
            ->with('status', 'Activities saved. The homepage cards are updated.');
    }

    private function admin(): Admin
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin, 401);

        return $admin;
    }
}
