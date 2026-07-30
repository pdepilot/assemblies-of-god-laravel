<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Controllers\Concerns\ResolvesSdtgAdmin;
use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgRegistrationReadService;
use App\Services\Sdtg\SdtgRegistrationWriteService;
use App\Services\Sdtg\SdtgVolunteerWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RegistrationsController
{
    use ResolvesSdtgAdmin;

    public function __construct(
        private readonly SdtgRegistrationReadService $read,
        private readonly SdtgRegistrationWriteService $write,
        private readonly SdtgVolunteerWriteService $volunteers,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.registrations.index', [
            'result' => $this->read->list(
                (string) $request->query('q', ''),
                (string) $request->query('status', ''),
                max(1, (int) $request->query('page', 1)),
            ),
            'stats' => $this->read->getStats(),
            'statuses' => SdtgRegistrationReadService::STATUSES,
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function show(int $registration): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);
        $row = $this->read->get($registration);
        abort_if($row === null, 404);

        return view('sdtg.registrations.show', [
            'registration' => $row,
            'statuses' => SdtgRegistrationReadService::STATUSES,
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function update(Request $request, int $registration): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->write->updateStatusAndNotes($registration, $request->only(['status', 'notes']));

        return back()->with('status', 'Registration updated.');
    }

    public function approveVolunteer(int $registration): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->volunteers->approve($registration);

        return back()->with('status', 'Volunteer approved.');
    }

    public function declineVolunteer(int $registration): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->volunteers->decline($registration);

        return back()->with('status', 'Volunteer declined.');
    }
}
