<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveLessonRequest;
use App\Models\Admin;
use App\Models\SundaySchoolLesson;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\LessonReadService;
use App\Services\SundaySchool\LessonWriteService;
use App\Services\SundaySchool\StudentReadService;
use App\Services\SundaySchool\SundaySchoolScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class LessonsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $studentRead,
        private readonly LessonReadService $read,
        private readonly LessonWriteService $write,
        private readonly SundaySchoolScopeService $scopeService,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $classId = (int) $request->query('class_id', 0);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $result = $this->read->listLessons($scope, $classId, $page, $perPage);

        return view('sunday-school.lessons.index', [
            'classes' => $this->studentRead->activeClassOptions($scope),
            'classId' => $classId,
            'items' => $result['items'],
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'canDelete' => $this->policy->deleteCurriculum($admin),
        ]);
    }

    public function create(): View
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        return view('sunday-school.lessons.create', $this->formData($scope));
    }

    public function store(SaveLessonRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->save($request->validated(), (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lesson_title' => $e->getMessage()]);
        }

        return redirect()->route('ss.lessons.index')->with('status', 'Lesson saved.');
    }

    public function show(SundaySchoolLesson $lesson): View
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $row = $this->read->getLesson((int) $lesson->id);

        if (! $row) {
            abort(404);
        }

        try {
            $this->scopeService->assertLessonInScope($scope, $row['class_id'] ?? null);
        } catch (InvalidArgumentException) {
            abort(403);
        }

        return view('sunday-school.lessons.show', [
            'lesson' => $row,
            'canDelete' => $this->policy->deleteCurriculum($admin),
        ]);
    }

    public function edit(SundaySchoolLesson $lesson): View
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $row = $this->read->getLesson((int) $lesson->id);

        if (! $row) {
            abort(404);
        }

        try {
            $this->scopeService->assertLessonInScope($scope, $row['class_id'] ?? null);
        } catch (InvalidArgumentException) {
            abort(403);
        }

        return view('sunday-school.lessons.edit', array_merge(
            $this->formData($scope),
            ['lesson' => $row],
        ));
    }

    public function update(SaveLessonRequest $request, SundaySchoolLesson $lesson): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->save(
                array_merge($request->validated(), ['id' => $lesson->id]),
                (int) $admin->id,
                $scope,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lesson_title' => $e->getMessage()]);
        }

        return redirect()->route('ss.lessons.show', $lesson)->with('status', 'Lesson updated.');
    }

    public function destroy(SundaySchoolLesson $lesson): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireDeleteCurriculum($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        try {
            $this->write->delete((int) $lesson->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('ss.lessons.index')->with('status', 'Lesson deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(array $scope): array
    {
        return [
            'classes' => $this->studentRead->activeClassOptions($scope),
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
