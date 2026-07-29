<?php

namespace App\Http\Controllers\Ministries;

use App\Models\Admin;
use App\Policies\MinistryPolicy;
use App\Services\Ministries\MinistryModuleReadService;
use App\Services\Ministries\MinistryModuleWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class MinistryModuleController
{
    public function __construct(
        private readonly MinistryModuleReadService $read,
        private readonly MinistryModuleWriteService $write,
        private readonly MinistryPolicy $policy,
    ) {}

    public function index(Request $request, string $ministryKey): View
    {
        $admin = $this->admin();
        $this->policy->requireViewMinistries($admin);

        $setting = $this->read->getSetting($ministryKey);
        abort_if($setting === null, 404);

        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $result = $this->read->listMembers($ministryKey, $query, $page, $perPage);

        return view('ministries.module.index', [
            'setting' => $setting,
            'items' => $result['items'],
            'attendanceOptions' => $this->read->listMembersForSelect($ministryKey),
            'churchMembers' => $this->read->listChurchMembersForImport($ministryKey),
            'stats' => $this->read->getStats($ministryKey),
            'birthdays' => $this->read->upcomingBirthdays($ministryKey, 30),
            'requiresParents' => $this->read->requiresParentDetails($ministryKey),
            'query' => $query,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'canManage' => $this->policy->manageMinistries($admin),
        ]);
    }

    public function register(Request $request, string $ministryKey): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMinistries($admin);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'parent_name' => ['nullable', 'string', 'max:160'],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'parent_email' => ['nullable', 'email', 'max:160'],
            'group_name' => ['nullable', 'string', 'max:80'],
            'role_note' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'joined_date' => ['nullable', 'date'],
        ]);

        try {
            $this->write->register($ministryKey, $validated, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('ministries.module.index', $ministryKey)
            ->with('status', 'Person added to this ministry roster. Church Members directory was not changed.');
    }

    public function importMember(Request $request, string $ministryKey): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMinistries($admin);

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'min:1', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->write->importFromChurchMember(
                $ministryKey,
                (int) $validated['member_id'],
                $validated['notes'] ?? null,
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['member_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('ministries.module.index', $ministryKey)
            ->with('status', 'Church member copied into this ministry roster (snapshot only).');
    }

    public function archive(Request $request, string $ministryKey, int $person): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMinistries($admin);

        try {
            $this->write->archive($ministryKey, $person);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['archive' => $e->getMessage()]);
        }

        return redirect()
            ->route('ministries.module.index', $ministryKey)
            ->with('status', 'Person removed from the active ministry roster.');
    }

    public function recordAttendance(Request $request, string $ministryKey): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMinistries($admin);

        try {
            [$source, $personId] = $this->parseAttendanceTarget($request);

            $this->write->recordAttendance(
                $ministryKey,
                $source,
                $personId,
                (string) $request->input('service_date', now()->toDateString()),
                $request->boolean('present', true),
                (int) $admin->id,
                $request->input('notes'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['attendee' => $e->getMessage()]);
        }

        return redirect()->route('ministries.module.index', $ministryKey)->with('status', 'Attendance recorded.');
    }

    /** @return array{0: string, 1: int} */
    private function parseAttendanceTarget(Request $request): array
    {
        $attendee = trim((string) $request->input('attendee', ''));
        if ($attendee !== '' && str_contains($attendee, ':')) {
            [$source, $id] = explode(':', $attendee, 2);
            $source = in_array($source, ['member', 'roster'], true) ? $source : 'roster';

            return [$source, max(1, (int) $id)];
        }

        $legacyId = (int) $request->input('roster_person_id', 0);
        if ($legacyId > 0) {
            return ['roster', $legacyId];
        }

        throw new InvalidArgumentException('Select a roster member.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
