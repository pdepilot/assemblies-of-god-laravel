<?php

namespace App\Http\Controllers\Settings;

use App\Models\Admin;
use App\Policies\SettingsPolicy;
use App\Services\Auth\RbacWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class RbacRolesController
{
    public function __construct(
        private readonly RbacWriteService $rbac,
        private readonly SettingsPolicy $policy,
    ) {}

    public function create(): View
    {
        $this->policy->requireManageRbac($this->admin());

        return view('settings.rbac.edit-role', [
            'role' => null,
            'permissionModules' => $this->rbac->listPermissionsGrouped()['modules'],
            'dashboardTypes' => $this->rbac->dashboardTypes(),
            'selectedPermissionIds' => [],
        ]);
    }

    public function edit(int $role): View
    {
        $this->policy->requireManageRbac($this->admin());
        $row = $this->rbac->getRole($role);
        abort_if($row === null, 404);

        return view('settings.rbac.edit-role', [
            'role' => $row,
            'permissionModules' => $this->rbac->listPermissionsGrouped()['modules'],
            'dashboardTypes' => $this->rbac->dashboardTypes(),
            'selectedPermissionIds' => $row['permission_ids'] ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->persist($request, null);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        return $this->persist($request, $role);
    }

    public function destroy(int $role): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageRbac($admin);

        try {
            $this->rbac->deleteRole($role, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'roles'])
                ->withErrors(['rbac' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'roles'])
            ->with('status', 'Role deleted.');
    }

    public function syncAdminRoles(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageRbac($admin);

        $data = $request->validate([
            'admin_id' => ['required', 'integer', 'min:1'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'min:1'],
        ]);

        try {
            $this->rbac->syncAdminRoles(
                (int) $data['admin_id'],
                array_map('intval', $data['role_ids'] ?? []),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'roles', 'assign_admin' => (int) $data['admin_id']])
                ->withErrors(['rbac' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'roles', 'assign_admin' => (int) $data['admin_id']])
            ->with('status', 'Administrator roles updated.');
    }

    private function persist(Request $request, ?int $roleId): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageRbac($admin);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'dashboard_type' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'min:1'],
        ]);

        if ($roleId !== null) {
            $data['id'] = $roleId;
        }
        $data['is_active'] = $request->boolean('is_active', true);

        try {
            $saved = $this->rbac->saveRole(
                $data,
                array_map('intval', $data['permission_ids'] ?? []),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['rbac' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.rbac.roles.edit', (int) $saved['id'])
            ->with('status', $roleId ? 'Role updated.' : 'Role created.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
