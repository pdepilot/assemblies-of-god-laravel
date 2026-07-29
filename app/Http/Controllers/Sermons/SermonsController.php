<?php

namespace App\Http\Controllers\Sermons;

use App\Http\Requests\Sermons\SaveSermonRequest;
use App\Models\Admin;
use App\Models\Sermon;
use App\Policies\SermonPolicy;
use App\Services\Sermons\SermonReadService;
use App\Services\Sermons\SermonWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class SermonsController
{
    public function __construct(
        private readonly SermonReadService $read,
        private readonly SermonWriteService $write,
        private readonly SermonPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSermons($admin);

        return view('sermons.sermons.index', [
            'result' => $this->read->listSermons(
                (string) $request->query('q', ''),
                (string) $request->query('status', ''),
                max(1, (int) $request->query('page', 1)),
            ),
            'statuses' => SermonReadService::STATUSES,
            'canManage' => $this->policy->manageSermons($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageSermons($this->admin());

        return view('sermons.sermons.create', [
            'categories' => $this->read->listCategories(),
            'series' => $this->read->listSeries(),
            'statuses' => SermonReadService::STATUSES,
            'types' => SermonReadService::TYPES,
        ]);
    }

    public function store(SaveSermonRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sermon.sermons.index')->with('status', 'Sermon saved.');
    }

    public function show(Sermon $sermon): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSermons($admin);
        $row = $this->read->getSermon((int) $sermon->id);
        abort_if($row === null, 404);

        return view('sermons.sermons.show', [
            'sermon' => $row,
            'canManage' => $this->policy->manageSermons($admin),
        ]);
    }

    public function edit(Sermon $sermon): View
    {
        $this->policy->requireManageSermons($this->admin());
        $row = $this->read->getSermon((int) $sermon->id);
        abort_if($row === null, 404);

        return view('sermons.sermons.edit', [
            'sermon' => $row,
            'categories' => $this->read->listCategories(),
            'series' => $this->read->listSeries(),
            'statuses' => SermonReadService::STATUSES,
            'types' => SermonReadService::TYPES,
        ]);
    }

    public function update(SaveSermonRequest $request, Sermon $sermon): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);

        $data = $request->validated();
        $data['id'] = (int) $sermon->id;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sermon.sermons.show', $sermon)->with('status', 'Sermon updated.');
    }

    public function publish(Sermon $sermon): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);
        $this->write->publish((int) $sermon->id, (int) $admin->id);

        return back()->with('status', 'Sermon published.');
    }

    public function archive(Sermon $sermon): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);
        $this->write->archive((int) $sermon->id, (int) $admin->id);

        return back()->with('status', 'Sermon archived.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
