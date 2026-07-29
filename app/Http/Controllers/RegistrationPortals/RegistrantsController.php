<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Models\Admin;
use App\Models\RegistrationPortal;
use App\Policies\RegistrationPortalPolicy;
use App\Services\RegistrationPortals\RegistrationPortalReadService;
use App\Services\RegistrationPortals\RegistrationPortalWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class RegistrantsController
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
        private readonly RegistrationPortalWriteService $write,
        private readonly RegistrationPortalPolicy $policy,
    ) {}

    public function index(Request $request, RegistrationPortal $registrationPortal): View
    {
        $admin = $this->admin();
        $this->policy->requireViewPortals($admin);

        $portal = $this->read->getPortal((int) $registrationPortal->id);
        abort_if($portal === null, 404);

        $status = (string) $request->query('status', '');
        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $result = $this->read->listRegistrants((int) $registrationPortal->id, $status, $query, $page, $perPage);

        return view('registration-portals.registrants.index', [
            'portal' => $portal,
            'items' => $result['items'],
            'status' => $status,
            'query' => $query,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'statuses' => RegistrationPortalReadService::REGISTRANT_STATUSES,
            'canManage' => $this->policy->managePortals($admin),
        ]);
    }

    public function show(RegistrationPortal $registrationPortal, int $registrant): View
    {
        $admin = $this->admin();
        $this->policy->requireViewPortals($admin);

        $portal = $this->read->getPortal((int) $registrationPortal->id);
        abort_if($portal === null, 404);

        $data = $this->read->getRegistrant($registrant);
        abort_if($data === null || (int) $data['portal_id'] !== (int) $registrationPortal->id, 404);

        return view('registration-portals.registrants.show', [
            'portal' => $portal,
            'registrant' => $data,
            'statuses' => RegistrationPortalReadService::REGISTRANT_STATUSES,
            'canManage' => $this->policy->managePortals($admin),
        ]);
    }

    public function updateStatus(Request $request, RegistrationPortal $registrationPortal, int $registrant): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManagePortals($admin);

        try {
            $this->write->updateRegistrantStatus($registrant, (string) $request->input('status'), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Registrant status updated.');
    }

    public function destroy(RegistrationPortal $registrationPortal, int $registrant): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManagePortals($admin);

        try {
            $this->write->deleteRegistrant($registrant, (int) $registrationPortal->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('registration-portals.registrants.index', $registrationPortal)
            ->with('status', 'Registrant deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
