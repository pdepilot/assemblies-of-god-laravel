<?php



namespace App\Http\Controllers\SundaySchool;



use App\Http\Requests\SundaySchool\SaveAttendanceRequest;

use App\Http\Requests\SundaySchool\VoidAttendanceRequest;

use App\Models\Admin;

use App\Policies\SundaySchoolPolicy;

use App\Services\SundaySchool\AttendanceReadService;

use App\Services\SundaySchool\AttendanceWriteService;

use App\Services\SundaySchool\ClassReadService;

use App\Services\SundaySchool\StudentReadService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\View\View;

use InvalidArgumentException;



final class AttendanceController

{

    public function __construct(

        private readonly ClassReadService $classRead,

        private readonly StudentReadService $studentRead,

        private readonly AttendanceReadService $read,

        private readonly AttendanceWriteService $write,

        private readonly SundaySchoolPolicy $policy,

    ) {}



    public function index(Request $request): View

    {

        $admin = $this->admin();

        $this->policy->requireRecordAttendance($admin);



        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        $classId = (int) $request->query('class_id', 0);

        $date = (string) $request->query('date', now()->toDateString());

        $classes = $this->studentRead->activeClassOptions($scope);



        $sheet = [];

        $sheetError = null;



        if ($classId > 0) {

            try {

                $sheet = $this->read->getSheet($classId, $date, $scope);

            } catch (InvalidArgumentException $e) {

                $sheetError = $e->getMessage();

            }

        }



        return view('sunday-school.attendance.index', [

            'classes' => $classes,

            'classId' => $classId,

            'date' => $date,

            'sheet' => $sheet,

            'sheetError' => $sheetError,

            'canViewRemovals' => $this->policy->viewAttendanceRemovals($admin),

        ]);

    }



    public function store(SaveAttendanceRequest $request): RedirectResponse

    {

        $admin = $this->admin();

        $this->policy->requireRecordAttendance($admin);



        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        $records = $request->validated('records');

        $result = $this->write->saveRegisterBatch($records, (int) $admin->id, $scope);



        $classId = (int) ($records[0]['class_id'] ?? 0);

        $date = (string) ($records[0]['attendance_date'] ?? now()->toDateString());



        $message = sprintf(

            'Register saved: %d attendance, %d offerings, %d memory verses.',

            $result['attendance'],

            $result['offerings'],

            $result['memory_verses'],

        );



        return redirect()

            ->route('ss.attendance.index', array_filter([

                'class_id' => $classId > 0 ? $classId : null,

                'date' => $date,

            ]))

            ->with('status', $message);

    }



    public function void(VoidAttendanceRequest $request): RedirectResponse

    {

        $admin = $this->admin();

        $this->policy->requireRecordAttendance($admin);



        $scope = $this->classRead->getAccessScope($this->adminPayload($admin));

        $data = $request->validated();



        try {

            $this->write->voidStudentRegisterEntry(

                (int) $data['student_id'],

                (int) $data['class_id'],

                (string) $data['attendance_date'],

                (string) $data['reason'],

                (int) $admin->id,

                $scope,

            );

        } catch (InvalidArgumentException $e) {

            return redirect()

                ->route('ss.attendance.index', array_filter([

                    'class_id' => (int) $data['class_id'],

                    'date' => (string) $data['attendance_date'],

                ]))

                ->withErrors(['void' => $e->getMessage()]);

        }



        return redirect()

            ->route('ss.attendance.index', array_filter([

                'class_id' => (int) $data['class_id'],

                'date' => (string) $data['attendance_date'],

            ]))

            ->with('status', 'Register entry removed and logged for review.');

    }



    public function removals(Request $request): View

    {

        $admin = $this->admin();

        $this->policy->requireViewAttendanceRemovals($admin);



        $page = max(1, (int) $request->query('page', 1));

        $perPage = max(1, min(100, (int) $request->query('per_page', 25)));

        $result = $this->read->listRemovals($page, $perPage);



        return view('sunday-school.attendance.removals', [

            'items' => $result['items'],

            'page' => $result['page'],

            'perPage' => $result['per_page'],

            'total' => $result['total'],

            'totalPages' => $result['pages'],

        ]);

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


