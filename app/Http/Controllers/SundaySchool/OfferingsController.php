<?php

namespace App\Http\Controllers\SundaySchool;

use App\Http\Requests\SundaySchool\SaveOfferingRequest;
use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\ClassReadService;
use App\Services\SundaySchool\OfferingReadService;
use App\Services\SundaySchool\OfferingWriteService;
use App\Services\SundaySchool\StudentReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class OfferingsController
{
    public function __construct(
        private readonly ClassReadService $classRead,
        private readonly StudentReadService $studentRead,
        private readonly OfferingReadService $read,
        private readonly OfferingWriteService $write,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageOfferings($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $classId = (int) $request->query('class_id', 0);
        $from = (string) $request->query('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        $result = $this->read->listOfferings($scope, $classId, $from, $to);

        return view('sunday-school.offerings.index', [
            'classes' => $this->studentRead->activeClassOptions($scope),
            'classId' => $classId,
            'from' => $from,
            'to' => $to,
            'items' => $result['items'],
            'total' => $result['total'],
            'students' => $classId > 0 ? $this->read->activeStudentsForClass($classId) : [],
        ]);
    }

    public function store(SaveOfferingRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageOfferings($admin);

        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));
        $data = $request->validated();

        try {
            $this->write->saveOffering($data, (int) $admin->id, $scope);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()
            ->route('ss.offerings.index', array_filter([
                'class_id' => (int) $data['class_id'],
                'from' => (string) $request->input('filter_from', now()->startOfMonth()->toDateString()),
                'to' => (string) $request->input('filter_to', now()->toDateString()),
            ]))
            ->with('status', 'Offering recorded.');
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
