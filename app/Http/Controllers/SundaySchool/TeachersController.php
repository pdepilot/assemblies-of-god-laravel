<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveTeacherRequest;
use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolTeacher;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\TeacherReadService;
use App\Services\SundaySchool\TeacherWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class TeachersController
{
    public function __construct(
        private readonly TeacherReadService $read,
        private readonly TeacherWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $result = $this->read->listTeachers($query, $status, $page, $perPage);

        return view('sunday-school.teachers.index', [
            'items' => $result['items'],
            'query' => $query,
            'status' => $status,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageTeachers($this->admin());

        return view('sunday-school.teachers.create', $this->formData());
    }

    public function store(SaveTeacherRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('ss.teachers.index')->with('status', 'Teacher saved.');
    }

    public function edit(SundaySchoolTeacher $teacher): View
    {
        $this->policy->requireManageTeachers($this->admin());

        return view('sunday-school.teachers.edit', array_merge(
            $this->formData(),
            ['teacher' => $teacher],
        ));
    }

    public function update(SaveTeacherRequest $request, SundaySchoolTeacher $teacher): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        try {
            $this->write->save(
                array_merge($request->validated(), ['id' => $teacher->id]),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('ss.teachers.index')->with('status', 'Teacher updated.');
    }

    public function suspend(SundaySchoolTeacher $teacher): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        $this->write->setStatus((int) $teacher->id, 'suspended', (int) $admin->id);

        return redirect()->route('ss.teachers.index')->with('status', 'Teacher suspended.');
    }

    public function activate(SundaySchoolTeacher $teacher): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        $this->write->setStatus((int) $teacher->id, 'active', (int) $admin->id);

        return redirect()->route('ss.teachers.index')->with('status', 'Teacher activated.');
    }

    public function destroy(SundaySchoolTeacher $teacher): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageTeachers($admin);

        $this->write->delete((int) $teacher->id, (int) $admin->id);

        return redirect()->route('ss.teachers.index')->with('status', 'Teacher deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'classes' => SundaySchoolClass::query()
                ->where('status', 'active')
                ->orderBy('class_name')
                ->get(['id', 'class_name', 'class_code']),
            'admins' => Admin::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'email', 'role']),
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
