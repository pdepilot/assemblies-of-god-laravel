<?php

namespace App\Http\Controllers\Events;

use App\Http\Requests\Events\SaveEventRequest;
use App\Models\Admin;
use App\Models\ChurchEvent;
use App\Policies\EventPolicy;
use App\Services\Events\EventReadService;
use App\Services\Events\EventWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class EventsController
{
    public function __construct(
        private readonly EventReadService $read,
        private readonly EventWriteService $write,
        private readonly EventPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewEvents($admin);

        $category = (string) $request->query('category', '');
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $result = $this->read->listEvents($category, $status, $page, $perPage);

        return view('events.index', [
            'items' => $result['items'],
            'featuredEvents' => $this->read->listEvents('', 'upcoming', 1, 6)['items'],
            'stats' => $this->read->getStats(),
            'category' => $category,
            'status' => $status,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'categories' => EventReadService::CATEGORIES,
            'categoryLabels' => EventReadService::categoryLabels(),
            'statuses' => EventReadService::STATUSES,
            'canManage' => $this->policy->manageEvents($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageEvents($this->admin());

        return view('events.create', array_merge(
            $this->formData(),
            ['eventData' => null],
        ));
    }

    public function store(SaveEventRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageEvents($admin);

        try {
            $this->write->create($request->validated(), $request->file('image'), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('events.index')->with('status', 'Event created.');
    }

    public function show(ChurchEvent $event): View
    {
        $admin = $this->admin();
        $this->policy->requireViewEvents($admin);
        $data = $this->read->getEvent((int) $event->id);
        abort_if($data === null, 404);

        return view('events.show', [
            'event' => $data,
            'categoryLabels' => EventReadService::categoryLabels(),
            'canManage' => $this->policy->manageEvents($admin),
        ]);
    }

    public function edit(ChurchEvent $event): View
    {
        $this->policy->requireManageEvents($this->admin());
        $data = $this->read->getEvent((int) $event->id);
        abort_if($data === null, 404);

        return view('events.edit', array_merge(
            $this->formData(),
            ['event' => $event, 'eventData' => $data],
        ));
    }

    public function update(SaveEventRequest $request, ChurchEvent $event): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageEvents($admin);

        try {
            $this->write->update(
                (int) $event->id,
                $request->validated(),
                $request->file('image'),
                $request->boolean('remove_image'),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('events.show', $event)->with('status', 'Event updated.');
    }

    public function setPublished(Request $request, ChurchEvent $event): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageEvents($admin);

        $this->write->setPublished((int) $event->id, $request->boolean('published'), (int) $admin->id);

        return back()->with('status', $request->boolean('published') ? 'Event published.' : 'Event paused.');
    }

    public function destroy(ChurchEvent $event): RedirectResponse
    {
        $this->policy->requireManageEvents($this->admin());

        try {
            $this->write->delete((int) $event->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('events.index')->with('status', 'Event deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'categories' => EventReadService::CATEGORIES,
            'categoryLabels' => EventReadService::categoryLabels(),
            'statuses' => EventReadService::STATUSES,
            'icons' => EventReadService::ICONS,
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
