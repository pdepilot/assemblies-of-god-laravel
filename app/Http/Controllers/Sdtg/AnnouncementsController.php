<?php

namespace App\Http\Controllers\Sdtg;

use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgAnnouncementReadService;
use App\Services\Sdtg\SdtgAnnouncementWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class AnnouncementsController
{
    public function __construct(
        private readonly SdtgAnnouncementReadService $read,
        private readonly SdtgAnnouncementWriteService $write,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.announcements.index', [
            'items' => $this->read->list(),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageSdtg($this->admin());

        return view('sdtg.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        try {
            $this->write->save($request->only(['title', 'excerpt', 'body', 'category', 'link_url', 'is_published']));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.announcements.index')->with('status', 'Announcement saved.');
    }

    public function show(int $announcement): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);
        $row = collect($this->read->list())->firstWhere('id', $announcement);
        abort_if($row === null, 404);

        return view('sdtg.announcements.show', [
            'announcement' => $row,
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function edit(int $announcement): View
    {
        $this->policy->requireManageSdtg($this->admin());
        $row = collect($this->read->list())->firstWhere('id', $announcement);
        abort_if($row === null, 404);

        return view('sdtg.announcements.edit', ['announcement' => $row]);
    }

    public function update(Request $request, int $announcement): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        $data = $request->only(['title', 'excerpt', 'body', 'category', 'link_url', 'is_published']);
        $data['id'] = $announcement;

        try {
            $this->write->save($data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sdtg.announcements.show', $announcement)->with('status', 'Announcement updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
