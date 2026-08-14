<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\SaveAdminPreferencesRequest;
use App\Http\Requests\Settings\SavePlatformSettingsRequest;
use App\Models\Admin;
use App\Policies\SettingsPolicy;
use App\Services\Auth\AdminWriteService;
use App\Services\Auth\RbacWriteService;
use App\Services\Settings\PlatformSettingsReadService;
use App\Services\Settings\PlatformSettingsWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class SettingsController
{
    public function __construct(
        private readonly PlatformSettingsReadService $read,
        private readonly PlatformSettingsWriteService $write,
        private readonly RbacWriteService $rbac,
        private readonly AdminWriteService $admins,
        private readonly SettingsPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSettings($admin);

        $activeTab = (string) request()->query('tab', 'overview');
        $canManageRbac = $this->policy->manageRbac($admin);

        $rbacPayload = null;
        $managedAdmins = [];
        $adminsQuery = trim((string) request()->query('q', ''));

        // Eager-load for super admins: settings tabs switch client-side without a reload.
        if ($canManageRbac) {
            $assignAdminId = max(0, (int) request()->query('assign_admin', 0));
            $assignmentAdmins = $this->rbac->listAdminsForAssignment(\App\Support\RbacPlatform::AG);
            $selectedAdmin = null;
            foreach ($assignmentAdmins as $row) {
                if ((int) $row['id'] === $assignAdminId) {
                    $selectedAdmin = $row;
                    break;
                }
            }
            if ($selectedAdmin === null && $assignmentAdmins !== []) {
                $selectedAdmin = $assignmentAdmins[0];
            }

            $rbacPayload = [
                'roles' => $this->rbac->listRoles(\App\Support\RbacPlatform::AG),
                'admins' => $assignmentAdmins,
                'selected_admin' => $selectedAdmin,
                'permission_count' => count($this->rbac->listPermissionsGrouped(\App\Support\RbacPlatform::AG)['items']),
            ];

            $managedAdmins = $this->admins->listAdmins($adminsQuery);
        }

        return view('settings.index', [
            'overview' => $this->read->overviewStats(),
            'groups' => $this->read->getEditableGroups(),
            'tabs' => $this->read->tabs($admin->isSuperAdmin() || (string) $admin->role === 'admin'),
            'admin' => $admin,
            'canManage' => $this->policy->manageSettings($admin),
            'canManageRbac' => $canManageRbac,
            'isSuper' => $admin->isSuperAdmin() || (string) $admin->role === 'super_admin',
            'activeTab' => $activeTab,
            'rbac' => $rbacPayload,
            'managedAdmins' => $managedAdmins,
            'adminsQuery' => $adminsQuery,
        ]);
    }

    public function updateGroup(SavePlatformSettingsRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageSettings($admin);

        $data = $request->validated();
        $group = (string) $data['group'];
        unset($data['group']);

        if ($group === 'rbac') {
            $this->policy->requireManageRbac($admin);
        }

        $payload = match ($group) {
            'general' => array_intersect_key($data, array_flip([
                'timezone', 'language', 'currency', 'date_format', 'records_per_page',
            ])),
            'church' => array_intersect_key($data, array_flip([
                'name', 'short_name', 'pastor', 'founded_year', 'address', 'city', 'state', 'phone', 'email', 'website',
                'service_sunday', 'service_midweek', 'social_facebook', 'social_instagram', 'social_youtube',
            ])),
            'website_design' => array_intersect_key($data, array_flip([
                'primary_color', 'secondary_color', 'dark_color', 'text_color', 'background_color', 'accent_color',
            ])),
            'rbac' => [
                'enforcement_enabled' => (bool) ($data['enforcement_enabled'] ?? false),
                'debug_enabled' => (bool) ($data['debug_enabled'] ?? false),
            ],
            default => [],
        };

        try {
            $this->write->saveGroup(
                $group,
                $payload,
                (int) $admin->id,
                $group === 'church' ? $request->file('logo') : null,
                $group === 'church' && $request->boolean('remove_logo'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['settings' => $e->getMessage()]);
        }

        $tab = $group === 'rbac' ? 'roles' : $group;

        return redirect()
            ->route('settings.index', ['tab' => $tab])
            ->with('status', ucfirst(str_replace('_', ' ', $group)).' settings saved.');
    }

    public function updatePreferences(SaveAdminPreferencesRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewSettings($admin);

        $this->write->updateAdminPreferences((int) $admin->id, $request->validated());

        return redirect()
            ->route('settings.index', ['tab' => 'preferences'])
            ->with('status', 'Your preferences were updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
