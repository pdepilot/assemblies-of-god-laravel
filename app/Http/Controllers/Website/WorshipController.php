<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SaveWorshipRequest;
use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\Website\WorshipReadService;
use App\Services\Website\WorshipWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WorshipController
{
    public function __construct(
        private readonly WorshipReadService $read,
        private readonly WorshipWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function edit(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        return view('website.worship.edit', [
            'programs' => $this->read->editorSlots(),
            'location' => $this->read->location(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function update(SaveWorshipRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        $this->write->save(
            $request->validated('programs') ?? [],
            (int) $admin->id,
            ['map_query' => (string) $request->input('worship_map_query', '')],
        );

        return redirect()
            ->route('website.worship.edit')
            ->with('status', 'Our Worship saved. The homepage cards and map are updated.');
    }

    private function admin(): Admin
    {
        $admin = auth('admin')->user();
        abort_unless($admin instanceof Admin, 401);

        return $admin;
    }
}
