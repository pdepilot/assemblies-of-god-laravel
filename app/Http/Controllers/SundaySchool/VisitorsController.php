<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveVisitorRequest;
use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\StudentReadService;
use App\Services\SundaySchool\VisitorReadService;
use App\Services\SundaySchool\VisitorWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class VisitorsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $studentRead,
        private readonly VisitorReadService $read,
        private readonly VisitorWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $classId = (int) $request->query('class_id', 0);
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        $result = $this->read->listVisitors($scope, $from, $to, $classId);

        return view('sunday-school.visitors.index', [
            'classes' => $this->studentRead->activeClassOptions($scope),
            'classId' => $classId,
            'from' => $from,
            'to' => $to,
            'items' => $result['items'],
            'total' => $result['total'],
        ]);
    }

    public function store(SaveVisitorRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->saveVisitor($request->validated(), (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['visitor_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.visitors.index', array_filter([
                'class_id' => (int) $request->input('class_id', 0) ?: null,
                'from' => (string) $request->input('filter_from', now()->startOfMonth()->toDateString()),
                'to' => (string) $request->input('filter_to', now()->toDateString()),
            ]))
            ->with('status', 'Visitor saved.');
    }

    public function update(SaveVisitorRequest $request, int $visitor): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $data = $request->validated();
        $data['id'] = $visitor;

        try {
            $this->write->saveVisitor($data, (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['visitor_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.visitors.index', array_filter([
                'class_id' => (int) $request->input('class_id', 0) ?: null,
                'from' => (string) $request->input('filter_from', now()->startOfMonth()->toDateString()),
                'to' => (string) $request->input('filter_to', now()->toDateString()),
            ]))
            ->with('status', 'Visitor updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }

    /** @return array{id: int, role: string} */
    private function adminPayload(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'role' => (string) $admin->role,
        ];
    }
}
