<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveStudentRequest;
use App\Http\Requests\SundaySchool\TransferStudentRequest;
use App\Models\Admin;
use App\Models\SundaySchoolStudent;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\StudentReadService;
use App\Services\SundaySchool\StudentWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class StudentsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $read,
        private readonly StudentWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        $query = trim((string) $request->query('q', ''));
        $classId = (int) $request->query('class_id', 0);
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $result = $this->read->listStudents($scope, $query, $classId, $status, $page, $perPage);

        return view('sunday-school.students.index', [
            'items' => $result['items'],
            'query' => $query,
            'classId' => $classId,
            'status' => $status,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'classes' => $this->read->activeClassOptions($scope),
            'canManage' => $this->policy->manageStudents($admin),
            'canDelete' => $this->policy->deleteStudents($admin),
        ]);
    }

    public function create(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        return view('sunday-school.students.create', $this->formData($scope));
    }

    public function store(SaveStudentRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->save($request->validated(), (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('ss.students.index')->with('status', 'Student saved.');
    }

    public function edit(SundaySchoolStudent $student): View
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        return view('sunday-school.students.edit', array_merge(
            $this->formData($scope),
            ['student' => $student],
        ));
    }

    public function update(SaveStudentRequest $request, SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->save(
                array_merge($request->validated(), ['id' => $student->id]),
                (int) $admin->id,
                $scope,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('ss.students.index')->with('status', 'Student updated.');
    }

    public function transfer(TransferStudentRequest $request, SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->transferToClass(
                (int) $student->id,
                (int) $request->validated('to_class_id'),
                (int) $admin->id,
                $scope,
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['to_class_id' => $e->getMessage()]);
        }

        return redirect()->route('ss.students.index')->with('status', 'Student transferred to new class.');
    }

    public function archive(SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageStudents($admin);
        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->archive((int) $student->id, (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('ss.students.index')->withErrors(['archive' => $e->getMessage()]);
        }

        return redirect()->route('ss.students.index')->with('status', 'Student archived.');
    }

    public function destroy(SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireDeleteStudents($admin);

        $this->write->delete((int) $student->id, (int) $admin->id);

        return redirect()->route('ss.students.index')->with('status', 'Student deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(array $scope): array
    {
        return [
            'classes' => $this->read->activeClassOptions($scope),
        ];
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
    private function adminPayload(Admin $admin): array
    {
        return [
            'id' => (int) $admin->id,
            'role' => (string) $admin->role,
        ];
    }
}
