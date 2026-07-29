<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\BulkPromoteRequest;
use App\Models\Admin;
use App\Models\SundaySchoolStudent;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\PromotionReadService;
use App\Services\SundaySchool\PromotionWriteService;
use App\Services\SundaySchool\StudentReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class PromotionsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $studentRead,
        private readonly PromotionReadService $read,
        private readonly PromotionWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $year = (int) $request->query('year', (int) date('Y'));

        return view('sunday-school.promotions.index', [
            'classes' => $this->studentRead->activeClassOptions($scope),
            'year' => $year,
            'evaluations' => $this->read->listEvaluations($year),
        ]);
    }

    public function bulkPromote(BulkPromoteRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $data = $request->validated();
        $toClassId = ($data['to_class_id'] ?? null) ? (int) $data['to_class_id'] : null;
        $result = $this->write->bulkPromoteClass((int) $data['from_class_id'], $toClassId, (int) $admin->id);

        $message = $toClassId
            ? "Bulk promotion complete: {$result['promoted']} student(s) promoted."
            : "Bulk graduation complete: {$result['graduated']} student(s) graduated.";

        return redirect()
            ->route('ss.promotions.index')
            ->with('status', $message);
    }

    public function promoteStudent(Request $request, SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $request->validate([
            'to_class_id' => ['required', 'integer', 'exists:sunday_school_classes,id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->write->promoteStudent(
                (int) $student->id,
                (int) $request->input('to_class_id'),
                (int) $admin->id,
                $request->input('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['promote' => $e->getMessage()]);
        }

        return redirect()->route('ss.promotions.index')->with('status', 'Student promoted.');
    }

    public function graduateStudent(SundaySchoolStudent $student): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        try {
            $this->write->graduateStudent((int) $student->id, (int) $admin->id, 'Manual graduation');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['graduate' => $e->getMessage()]);
        }

        return redirect()->route('ss.promotions.index')->with('status', 'Student graduated.');
    }

    public function evaluateYear(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $year = (int) $request->input('year', (int) date('Y'));
        $results = $this->write->evaluatePromotionYear($year, (int) $admin->id);

        return redirect()
            ->route('ss.promotions.index', ['year' => $year])
            ->with('status', count($results).' student evaluation(s) recorded for '.$year.'.');
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
