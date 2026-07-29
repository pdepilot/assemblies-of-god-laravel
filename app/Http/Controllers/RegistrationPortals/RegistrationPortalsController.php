<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Http\Requests\RegistrationPortals\SaveRegistrationPortalRequest;
use App\Models\Admin;
use App\Models\RegistrationPortal;
use App\Policies\RegistrationPortalPolicy;
use App\Services\RegistrationPortals\RegistrationPortalReadService;
use App\Services\RegistrationPortals\RegistrationPortalWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class RegistrationPortalsController
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
        private readonly RegistrationPortalWriteService $write,
        private readonly RegistrationPortalPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewPortals($admin);

        $status = (string) $request->query('status', '');
        $query = trim((string) $request->query('q', ''));

        return view('registration-portals.index', [
            'items' => $this->read->listPortals($status, $query),
            'stats' => $this->read->getDashboardStats(),
            'status' => $status,
            'query' => $query,
            'statuses' => RegistrationPortalReadService::PORTAL_STATUSES,
            'canManage' => $this->policy->managePortals($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManagePortals($this->admin());

        return view('registration-portals.create', [
            'statuses' => RegistrationPortalReadService::PORTAL_STATUSES,
            'templates' => $this->write->templateCatalog(),
            'portalFields' => [],
        ]);
    }

    public function store(SaveRegistrationPortalRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManagePortals($admin);

        try {
            $portal = $this->write->savePortal($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['event_name' => $e->getMessage()]);
        }

        return redirect()->route('registration-portals.show', $portal['id'])
            ->with('status', 'Registration form created. Use Preview/Open form or QR code to share it.');
    }

    public function show(RegistrationPortal $registrationPortal): View
    {
        $admin = $this->admin();
        $this->policy->requireViewPortals($admin);
        $data = $this->read->getPortal((int) $registrationPortal->id);
        abort_if($data === null, 404);

        return view('registration-portals.show', [
            'portal' => $data,
            'statuses' => RegistrationPortalReadService::PORTAL_STATUSES,
            'canManage' => $this->policy->managePortals($admin),
        ]);
    }

    public function edit(RegistrationPortal $registrationPortal): View
    {
        $this->policy->requireManagePortals($this->admin());
        $data = $this->read->getPortal((int) $registrationPortal->id);
        abort_if($data === null, 404);

        return view('registration-portals.edit', [
            'portal' => $registrationPortal,
            'portalFields' => $data['fields'] ?? [],
            'statuses' => RegistrationPortalReadService::PORTAL_STATUSES,
            'templates' => $this->write->templateCatalog(),
        ]);
    }

    public function update(SaveRegistrationPortalRequest $request, RegistrationPortal $registrationPortal): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManagePortals($admin);

        try {
            $this->write->savePortal([
                ...$request->validated(),
                'id' => $registrationPortal->id,
            ], (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['event_name' => $e->getMessage()]);
        }

        return redirect()->route('registration-portals.show', $registrationPortal)->with('status', 'Portal updated.');
    }

    public function updateStatus(Request $request, RegistrationPortal $registrationPortal): RedirectResponse
    {
        $this->policy->requireManagePortals($this->admin());

        try {
            $this->write->updatePortalStatus((int) $registrationPortal->id, (string) $request->input('status'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Portal status updated.');
    }

    public function destroy(RegistrationPortal $registrationPortal): RedirectResponse
    {
        $this->policy->requireManagePortals($this->admin());

        try {
            $this->write->deletePortal((int) $registrationPortal->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['event_name' => $e->getMessage()]);
        }

        return redirect()->route('registration-portals.index')->with('status', 'Portal deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
