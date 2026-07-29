<?php

namespace App\Http\Controllers\Settings;

use App\Models\Admin;
use App\Policies\SettingsPolicy;
use App\Services\Auth\AdminWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class AdminsController
{
    public function __construct(
        private readonly AdminWriteService $admins,
        private readonly SettingsPolicy $policy,
    ) {}

    public function create(): View
    {
        $this->policy->requireManageRbac($this->admin());

        return view('settings.admins.edit', [
            'managedAdmin' => null,
            'assignableRoles' => $this->admins->listAssignableRoles(includeSuperAdmin: false),
            'accountStatuses' => $this->accountStatuses(),
        ]);
    }

    public function edit(int $admin): View
    {
        $this->policy->requireManageRbac($this->admin());
        $row = $this->admins->getAdmin($admin);
        abort_if($row === null, 404);

        return view('settings.admins.edit', [
            'managedAdmin' => $row,
            'assignableRoles' => $this->admins->listAssignableRoles(includeSuperAdmin: ! empty($row['is_super_admin'])),
            'accountStatuses' => $this->accountStatuses(),
        ]);
    }

    public function permissions(int $admin): View
    {
        $this->policy->requireManageRbac($this->admin());

        try {
            $report = $this->admins->permissionsReport($admin);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return view('settings.admins.permissions', $report);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->admin();
        $this->policy->requireManageRbac($actor);

        $data = $this->validated($request, creating: true);

        try {
            $created = $this->admins->createAdmin($data, (int) $actor->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['admin' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.admins.edit', $created['id'])
            ->with('status', 'Administrator created. Role permissions are applied from the selected role.');
    }

    public function update(Request $request, int $admin): RedirectResponse
    {
        $actor = $this->admin();
        $this->policy->requireManageRbac($actor);

        $data = $this->validated($request, creating: false);
        $data['unlock'] = $request->boolean('unlock');

        try {
            $this->admins->updateAdmin($admin, $data, (int) $actor->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['admin' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'admins'])
            ->with('status', 'Administrator updated.');
    }

    public function destroy(int $admin): RedirectResponse
    {
        $actor = $this->admin();
        $this->policy->requireManageRbac($actor);

        try {
            $this->admins->deleteAdmin($admin, (int) $actor->id);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'admins'])
                ->withErrors(['admin' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'admins'])
            ->with('status', 'Administrator deleted.');
    }

    public function updateStatus(Request $request, int $admin): RedirectResponse
    {
        $actor = $this->admin();
        $this->policy->requireManageRbac($actor);

        $data = $request->validate([
            'account_status' => ['required', 'in:active,suspended'],
        ]);

        try {
            $updated = $this->admins->setAccountStatus($admin, (string) $data['account_status'], (int) $actor->id);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'admins'])
                ->withErrors(['admin' => $e->getMessage()]);
        }

        $message = (($updated['account_status'] ?? '') === 'suspended')
            ? 'Administrator suspended. They can no longer sign in.'
            : 'Administrator reactivated. They can sign in again.';

        return redirect()
            ->route('settings.index', ['tab' => 'admins'])
            ->with('status', $message);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'username' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:120'],
            'rbac_role_id' => [
                $creating ? 'required' : 'nullable',
                'integer',
                'min:1',
                Rule::exists('roles', 'id')->where('is_active', 1),
            ],
            'account_status' => ['required', 'in:active,suspended,locked,pending'],
            'recovery_email' => ['nullable', 'email', 'max:191'],
            'recovery_phone' => ['nullable', 'string', 'max:30'],
            'force_password_change' => ['nullable', 'boolean'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'max:191'],
        ];

        $data = $request->validate($rules);
        $data['force_password_change'] = $request->boolean('force_password_change');

        return $data;
    }

    /** @return list<array{value: string, label: string}> */
    private function accountStatuses(): array
    {
        return [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'suspended', 'label' => 'Suspended'],
            ['value' => 'locked', 'label' => 'Locked'],
        ];
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
