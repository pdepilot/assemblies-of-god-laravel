<?php

namespace App\Http\Controllers\Sermons;

use App\Http\Requests\Sermons\SaveBroadcastRequest;
use App\Models\Admin;
use App\Models\LiveStream;
use App\Policies\SermonPolicy;
use App\Services\Sermons\BroadcastReadService;
use App\Services\Sermons\BroadcastWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class BroadcastsController
{
    public function __construct(
        private readonly BroadcastReadService $read,
        private readonly BroadcastWriteService $write,
        private readonly SermonPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSermons($admin);

        return view('sermons.broadcasts.index', [
            'result' => $this->read->listStreams(
                (string) $request->query('status', ''),
                max(1, (int) $request->query('page', 1)),
            ),
            'statuses' => BroadcastReadService::STATUSES,
            'canManage' => $this->policy->manageSermons($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageSermons($this->admin());

        return view('sermons.broadcasts.create', [
            'statuses' => BroadcastReadService::STATUSES,
        ]);
    }

    public function store(SaveBroadcastRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sermon.broadcasts.index')->with('status', 'Broadcast saved.');
    }

    public function show(LiveStream $broadcast): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSermons($admin);
        $row = $this->read->getStream((int) $broadcast->id);
        abort_if($row === null, 404);

        return view('sermons.broadcasts.show', [
            'broadcast' => $row,
            'canManage' => $this->policy->manageSermons($admin),
        ]);
    }

    public function edit(LiveStream $broadcast): View
    {
        $this->policy->requireManageSermons($this->admin());
        $row = $this->read->getStream((int) $broadcast->id);
        abort_if($row === null, 404);

        return view('sermons.broadcasts.edit', [
            'broadcast' => $row,
            'statuses' => BroadcastReadService::STATUSES,
        ]);
    }

    public function update(SaveBroadcastRequest $request, LiveStream $broadcast): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSermons($admin);

        $data = $request->validated();
        $data['id'] = (int) $broadcast->id;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('sermon.broadcasts.show', $broadcast)->with('status', 'Broadcast updated.');
    }

    public function start(LiveStream $broadcast): RedirectResponse
    {
        $this->policy->requireManageSermons($this->admin());
        $this->write->startStream((int) $broadcast->id);

        return back()->with('status', 'Broadcast is now live.');
    }

    public function stop(LiveStream $broadcast): RedirectResponse
    {
        $this->policy->requireManageSermons($this->admin());
        $this->write->stopStream((int) $broadcast->id);

        return back()->with('status', 'Broadcast ended.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
