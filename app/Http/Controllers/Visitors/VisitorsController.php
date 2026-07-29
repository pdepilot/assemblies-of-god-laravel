<?php

namespace App\Http\Controllers\Visitors;

use App\Http\Requests\Visitors\PromoteVisitorRequest;
use App\Http\Requests\Visitors\SaveVisitorRequest;
use App\Models\Admin;
use App\Models\Visitor;
use App\Policies\VisitorPolicy;
use App\Services\Members\MemberReadService;
use App\Services\Visitors\VisitorReadService;
use App\Services\Visitors\VisitorWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class VisitorsController
{
    public function __construct(
        private readonly VisitorReadService $read,
        private readonly VisitorWriteService $write,
        private readonly MemberReadService $members,
        private readonly VisitorPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewVisitors($admin);

        $query = trim((string) $request->query('q', ''));
        $followUp = (string) $request->query('follow_up', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $result = $this->read->listVisitors($query, $followUp, $page, $perPage);

        return view('visitors.index', [
            'items' => $result['items'],
            'stats' => $this->read->getStats(),
            'query' => $query,
            'followUp' => $followUp,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'followUpStatuses' => VisitorReadService::FOLLOW_UP_STATUSES,
            'followUpLabels' => VisitorReadService::followUpLabels(),
            'canManage' => $this->policy->manageVisitors($admin),
            'canDelete' => $this->policy->deleteVisitors($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageVisitors($this->admin());

        return view('visitors.create', $this->formData());
    }

    public function store(SaveVisitorRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageVisitors($admin);

        try {
            $this->write->create($request->validated(), $request->file('photo'), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('visitors.index')->with('status', 'Visitor registered.');
    }

    public function show(Visitor $visitor): View
    {
        $admin = $this->admin();
        $this->policy->requireViewVisitors($admin);
        $data = $this->read->getVisitor((int) $visitor->id);
        abort_if($data === null, 404);

        return view('visitors.show', [
            'visitor' => $data,
            'followUpLabels' => VisitorReadService::followUpLabels(),
            'canManage' => $this->policy->manageVisitors($admin),
            'canDelete' => $this->policy->deleteVisitors($admin),
        ]);
    }

    public function edit(Visitor $visitor): View
    {
        $this->policy->requireManageVisitors($this->admin());
        abort_if((string) $visitor->follow_up_status === 'promoted', 403, 'Promoted visitors cannot be edited.');

        return view('visitors.edit', array_merge(
            $this->formData(),
            ['visitor' => $visitor],
        ));
    }

    public function update(SaveVisitorRequest $request, Visitor $visitor): RedirectResponse
    {
        $this->policy->requireManageVisitors($this->admin());

        try {
            $this->write->update(
                (int) $visitor->id,
                $request->validated(),
                $request->file('photo'),
                $request->boolean('remove_photo'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('visitors.show', $visitor)->with('status', 'Visitor updated.');
    }

    public function recordReturn(Request $request, Visitor $visitor): RedirectResponse
    {
        $this->policy->requireManageVisitors($this->admin());

        try {
            $this->write->recordReturnVisit(
                (int) $visitor->id,
                $request->input('visit_date'),
                $request->input('service_attended'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['visit_date' => $e->getMessage()]);
        }

        return redirect()->route('visitors.show', $visitor)->with('status', 'Return visit recorded.');
    }

    public function promote(PromoteVisitorRequest $request, Visitor $visitor): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageVisitors($admin);

        try {
            $result = $this->write->promoteToMember((int) $visitor->id, $request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['confirm_membership' => $e->getMessage()]);
        }

        return redirect()
            ->route('members.show', $result['member']['id'])
            ->with('status', 'Visitor promoted to full membership.');
    }

    public function destroy(Visitor $visitor): RedirectResponse
    {
        $this->policy->requireDeleteVisitors($this->admin());

        try {
            $this->write->delete((int) $visitor->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('visitors.index')->with('status', 'Visitor deleted.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'services' => VisitorReadService::SERVICES,
            'howHeard' => VisitorReadService::HOW_HEARD,
            'departments' => $this->members->listDepartmentOptions(),
            'followUpStatuses' => array_filter(
                VisitorReadService::FOLLOW_UP_STATUSES,
                static fn (string $s) => $s !== 'promoted',
            ),
            'followUpLabels' => VisitorReadService::followUpLabels(),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
