<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveClassRequest;
use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolTeacher;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\ClassWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class ClassesController
{
    public function __construct(
        private readonly ClassReadService $read,
        private readonly ClassWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $scope = $this->read->getAccessScope($this->adminScope($admin));

        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $result = $this->read->listClasses($scope, $status, $page, $perPage);
        $total = $result['total'];
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        return view('sunday-school.classes.index', [
            'items' => $result['items'],
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => max(1, $totalPages),
            'canManage' => $this->policy->manageClasses($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageClasses($this->admin());

        return view('sunday-school.classes.create', $this->formData());
    }

    public function store(SaveClassRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['class_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.classes.index')
            ->with('status', 'Class saved.');
    }

    public function edit(SundaySchoolClass $class): View
    {
        $this->policy->requireManageClasses($this->admin());

        return view('sunday-school.classes.edit', array_merge(
            $this->formData(),
            ['class' => $class],
        ));
    }

    public function update(SaveClassRequest $request, SundaySchoolClass $class): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        try {
            $this->write->save(
                array_merge($request->validated(), ['id' => $class->id]),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['class_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.classes.index')
            ->with('status', 'Class updated.');
    }

    public function archive(SundaySchoolClass $class): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $this->write->archive((int) $class->id, (int) $admin->id);

        return redirect()
            ->route('ss.classes.index')
            ->with('status', 'Class archived.');
    }

    public function destroy(SundaySchoolClass $class): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        try {
            $this->write->delete((int) $class->id, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('ss.classes.index')
                ->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.classes.index')
            ->with('status', 'Class deleted.');
    }

    public function seedDefaults(): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $count = $this->write->seedDefaultClasses((int) $admin->id);

        return redirect()
            ->route('ss.classes.index')
            ->with('status', "Seeded {$count} default classes.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $teachers = SundaySchoolTeacher::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return ['teachers' => $teachers];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }

    /**
     * @return array{id: int, role: string}
     */
    private function adminScope(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'role' => (string) $admin->role,
        ];
    }
}
