<?php

namespace App\Http\Controllers\Members;

use App\Http\Requests\Members\SaveMemberRequest;
use App\Models\Admin;
use App\Models\Member;
use App\Policies\MemberPolicy;
use App\Services\Members\MemberReadService;
use App\Services\Members\MemberWriteService;
use App\Services\Ministries\MinistryModuleReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class MembersController
{
    public function __construct(
        private readonly MemberReadService $read,
        private readonly MemberWriteService $write,
        private readonly MinistryModuleReadService $ministryRead,
        private readonly MemberPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewMembers($admin);

        $query = trim((string) $request->query('q', ''));
        $department = (string) $request->query('department', '');
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $result = $this->read->listMembers($query, $department, $status, $page, $perPage);
        $ministryTotals = $this->ministryRead->getAllRosterTotals();

        return view('members.index', [
            'items' => $result['items'],
            'stats' => $this->read->getStats(),
            'ministryTotals' => $ministryTotals['ministries'],
            'ministryGrandTotal' => $ministryTotals['grand_total'],
            'query' => $query,
            'department' => $department,
            'status' => $status,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'departments' => $this->read->listDepartmentOptions(),
            'statuses' => MemberReadService::STATUSES,
            'statusLabels' => MemberReadService::statusLabels(),
            'canManage' => $this->policy->manageMembers($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageMembers($this->admin());

        return view('members.create', $this->formData());
    }

    public function store(SaveMemberRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMembers($admin);

        try {
            $this->write->create($request->validated(), $request->file('photo'), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('members.index')->with('status', 'Member registered.');
    }

    public function show(Member $member): View
    {
        $this->policy->requireViewMembers($this->admin());
        $data = $this->read->getMember((int) $member->id);
        abort_if($data === null, 404);

        return view('members.show', [
            'member' => $data,
            'statusLabels' => MemberReadService::statusLabels(),
            'canManage' => $this->policy->manageMembers($this->admin()),
        ]);
    }

    public function edit(Member $member): View
    {
        $this->policy->requireManageMembers($this->admin());

        return view('members.edit', array_merge(
            $this->formData(),
            ['member' => $member],
        ));
    }

    public function update(SaveMemberRequest $request, Member $member): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMembers($admin);

        try {
            $this->write->update(
                (int) $member->id,
                $request->validated(),
                $request->file('photo'),
                $request->boolean('remove_photo'),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('members.show', $member)->with('status', 'Member updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'departments' => $this->read->listDepartmentOptions(),
            'statuses' => MemberReadService::STATUSES,
            'statusLabels' => MemberReadService::statusLabels(),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
