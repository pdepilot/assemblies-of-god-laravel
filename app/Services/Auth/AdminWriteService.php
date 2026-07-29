<?php

namespace App\Services\Auth;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class AdminWriteService
{
    /** @var list<string> */
    private const LEGACY_ROLES = ['super_admin', 'admin', 'ss_teacher', 'ss_superintendent'];

    /** @var list<string> */
    private const ACCOUNT_STATUSES = ['active', 'suspended', 'locked', 'pending'];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAdmins(string $query = ''): array
    {
        if (! Schema::hasTable('admins')) {
            return [];
        }

        $builder = DB::table('admins as a')->orderByDesc('a.id');
        $query = trim($query);
        if ($query !== '') {
            $like = '%'.$query.'%';
            $builder->where(function ($q) use ($like) {
                $q->where('a.full_name', 'like', $like)
                    ->orWhere('a.email', 'like', $like)
                    ->orWhere('a.username', 'like', $like)
                    ->orWhere('a.phone', 'like', $like);
            });
        }

        return $builder
            ->get([
                'a.id',
                'a.full_name',
                'a.email',
                'a.username',
                'a.phone',
                'a.department',
                'a.position',
                'a.role',
                'a.role_id',
                'a.is_active',
                'a.account_status',
                'a.locked_at',
                'a.last_login_at',
                'a.created_at',
            ])
            ->map(fn ($row) => $this->formatAdmin((array) $row))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getAdmin(int $id): ?array
    {
        if (! Schema::hasTable('admins') || $id < 1) {
            return null;
        }

        $row = DB::table('admins')->where('id', $id)->first();

        return $row ? $this->formatAdmin((array) $row) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createAdmin(array $data, int $createdBy): array
    {
        if (! Schema::hasTable('admins')) {
            throw new InvalidArgumentException('Administrators storage is unavailable.');
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $fullName === '') {
            throw new InvalidArgumentException('Full name and email are required.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }
        if (DB::table('admins')->where('email', $email)->exists()) {
            throw new InvalidArgumentException('An administrator with this email already exists.');
        }

        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '') {
            $username = (string) (strstr($email, '@', true) ?: $email);
        }

        $status = $this->normalizeStatus((string) ($data['account_status'] ?? 'active'));
        $selection = $this->resolveRoleSelection($data, allowSuper: false);
        if ($selection['role_id'] === null) {
            throw new InvalidArgumentException('A project role is required.');
        }
        $hash = Hash::make($password);

        $payload = [
            'email' => $email,
            'username' => $username,
            'password_hash' => $hash,
            'full_name' => $fullName,
            'phone' => $this->nullableString($data['phone'] ?? null),
            'department' => $this->nullableString($data['department'] ?? null),
            'position' => $this->nullableString($data['position'] ?? null),
            'role' => $selection['role'],
            'role_id' => $selection['role_id'],
            'account_status' => $status,
            'is_active' => in_array($status, ['active', 'pending'], true) ? 1 : 0,
            'force_password_change' => ! empty($data['force_password_change']) ? 1 : 0,
            'locked_at' => $status === 'locked' ? now() : null,
            'recovery_email' => $this->nullableString($data['recovery_email'] ?? null),
            'recovery_phone' => $this->nullableString($data['recovery_phone'] ?? null),
            'created_by' => $createdBy > 0 ? $createdBy : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($this->shouldSyncErpPassword($selection) && Schema::hasColumn('admins', 'erp_password_hash')) {
            $payload['erp_password_hash'] = $hash;
        }

        $id = (int) DB::table('admins')->insertGetId($payload);
        if ($selection['role_id'] !== null) {
            $this->assignPrimaryRole($id, (int) $selection['role_id'], $createdBy);
        }
        $this->audit('admin_created', 'Administrator created: '.$email, $createdBy);

        return $this->getAdmin($id) ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateAdmin(int $id, array $data, int $actorId): array
    {
        $existing = $this->getAdmin($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }

        $email = strtolower(trim((string) ($data['email'] ?? $existing['email'])));
        $fullName = trim((string) ($data['full_name'] ?? $existing['full_name']));
        if ($email === '' || $fullName === '') {
            throw new InvalidArgumentException('Full name and email are required.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }
        if (DB::table('admins')->where('email', $email)->where('id', '!=', $id)->exists()) {
            throw new InvalidArgumentException('An administrator with this email already exists.');
        }

        $isExistingSuper = $this->isSuperAdminRow($existing);
        $selection = $isExistingSuper
            ? ['role' => 'super_admin', 'role_id' => $existing['role_id'] ?? null, 'slug' => 'super_admin']
            : $this->resolveRoleSelection($data, allowSuper: false);

        if (! $isExistingSuper && empty($selection['role_id'])) {
            throw new InvalidArgumentException('A project role is required.');
        }

        $status = $this->normalizeStatus((string) ($data['account_status'] ?? $existing['account_status']));
        if ($isExistingSuper && $status !== 'active') {
            throw new InvalidArgumentException('Super Admin accounts must remain active.');
        }

        $username = trim((string) ($data['username'] ?? $existing['username'] ?? ''));
        if ($username === '') {
            $username = (string) (strstr($email, '@', true) ?: $email);
        }

        $update = [
            'email' => $email,
            'username' => $username,
            'full_name' => $fullName,
            'phone' => $this->nullableString($data['phone'] ?? $existing['phone'] ?? null),
            'department' => $this->nullableString($data['department'] ?? $existing['department'] ?? null),
            'position' => $this->nullableString($data['position'] ?? $existing['position'] ?? null),
            'role' => $selection['role'],
            'role_id' => $selection['role_id'],
            'account_status' => $status,
            'is_active' => in_array($status, ['active', 'pending'], true) ? 1 : 0,
            'force_password_change' => ! empty($data['force_password_change']) ? 1 : 0,
            'recovery_email' => $this->nullableString($data['recovery_email'] ?? $existing['recovery_email'] ?? null),
            'recovery_phone' => $this->nullableString($data['recovery_phone'] ?? $existing['recovery_phone'] ?? null),
            'updated_at' => now(),
        ];

        if ($status === 'locked') {
            $update['locked_at'] = $existing['locked_at'] ?: now();
        } elseif (! empty($data['unlock'])) {
            $update['locked_at'] = null;
            $update['account_status'] = 'active';
            $update['is_active'] = 1;
        } else {
            $update['locked_at'] = null;
        }

        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Password must be at least 8 characters.');
            }
            $hash = Hash::make($password);
            $update['password_hash'] = $hash;
            if ($this->shouldSyncErpPassword($selection) && Schema::hasColumn('admins', 'erp_password_hash')) {
                $update['erp_password_hash'] = $hash;
            }
        }

        DB::table('admins')->where('id', $id)->update($update);

        if (! $isExistingSuper && $selection['role_id'] !== null) {
            $this->assignPrimaryRole($id, (int) $selection['role_id'], $actorId);
        }

        $this->audit('admin_updated', 'Administrator updated: '.$email, $actorId);

        return $this->getAdmin($id) ?? [];
    }

    public function deleteAdmin(int $id, int $actorId): void
    {
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        $admin = $this->getAdmin($id);
        if ($admin === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }
        if ($this->isSuperAdminRow($admin)) {
            throw new InvalidArgumentException('Super Admin accounts cannot be deleted.');
        }

        DB::transaction(function () use ($id) {
            if (Schema::hasTable('admin_role_assignments')) {
                DB::table('admin_role_assignments')->where('admin_id', $id)->delete();
            }
            if (Schema::hasTable('admin_permissions')) {
                DB::table('admin_permissions')->where('admin_id', $id)->delete();
            }
            if (Schema::hasTable('admin_sessions')) {
                DB::table('admin_sessions')->where('admin_id', $id)->delete();
            }
            DB::table('admins')->where('id', $id)->delete();
        });

        $this->audit('admin_deleted', 'Administrator deleted: '.(string) $admin['email'], $actorId, 'warning');
    }

    /**
     * Active RBAC roles for the admin form dropdown.
     *
     * @return list<array{id: int, slug: string, name: string, dashboard_type: string, is_system: bool}>
     */
    public function listAssignableRoles(bool $includeSuperAdmin = false): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }

        $query = DB::table('roles')
            ->where('is_active', 1)
            ->orderByDesc('is_system')
            ->orderBy('name');

        if (! $includeSuperAdmin) {
            $query->where('slug', '!=', 'super_admin');
        }

        return $query
            ->get(['id', 'slug', 'name', 'dashboard_type', 'is_system'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'slug' => (string) $row->slug,
                'name' => (string) $row->name,
                'dashboard_type' => (string) ($row->dashboard_type ?? ''),
                'is_system' => ! empty($row->is_system),
            ])
            ->all();
    }

    /**
     * Map an RBAC role slug to the legacy admins.role enum value.
     */
    public function legacyRoleForSlug(string $slug): string
    {
        $slug = trim($slug);

        return match (true) {
            $slug === 'super_admin' => 'super_admin',
            $slug === 'sunday_school_teacher', str_contains($slug, 'ss_teacher') => 'ss_teacher',
            $slug === 'sunday_school_superintendent', str_contains($slug, 'superintendent') => 'ss_superintendent',
            default => 'admin',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{role: string, role_id: int|null, slug: string|null}
     */
    private function resolveRoleSelection(array $data, bool $allowSuper): array
    {
        $roleId = (int) ($data['rbac_role_id'] ?? $data['role_id'] ?? 0);
        if ($roleId > 0 && Schema::hasTable('roles')) {
            $role = DB::table('roles')->where('id', $roleId)->where('is_active', 1)->first(['id', 'slug']);
            if ($role === null) {
                throw new InvalidArgumentException('Selected role was not found or is inactive.');
            }
            $slug = (string) $role->slug;
            if ($slug === 'super_admin' && ! $allowSuper) {
                throw new InvalidArgumentException('Super Admin cannot be assigned from this form.');
            }

            return [
                'role' => $this->legacyRoleForSlug($slug),
                'role_id' => (int) $role->id,
                'slug' => $slug,
            ];
        }

        // Backward-compatible: legacy string role when no RBAC id provided.
        $legacy = $this->normalizeLegacyRole((string) ($data['role'] ?? 'admin'), allowSuper: $allowSuper);

        return [
            'role' => $legacy,
            'role_id' => null,
            'slug' => null,
        ];
    }

    private function assignPrimaryRole(int $adminId, int $roleId, int $actorId): void
    {
        if ($roleId < 1 || ! Schema::hasTable('admin_role_assignments')) {
            return;
        }

        app(RbacWriteService::class)->syncAdminRoles($adminId, [$roleId], $actorId);
    }

    /**
     * @return array{
     *   admin: array<string, mixed>,
     *   access_mode: string,
     *   access_label: string,
     *   roles: list<array{id: int, name: string, slug: string, permission_count: int}>,
     *   permission_count: int,
     *   modules: list<array{key: string, label: string, permissions: list<array<string, mixed>>}>
     * }
     */
    public function permissionsReport(int $adminId): array
    {
        $admin = $this->getAdmin($adminId);
        if ($admin === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }

        $model = Admin::query()->find($adminId);
        if ($model === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }

        $rbac = app(RbacReadService::class);
        $isSuper = $rbac->isSuperAdmin($model);
        $isLegacy = $rbac->hasLegacyFullAccess($model);

        if ($isSuper) {
            $accessMode = 'full_super';
            $accessLabel = 'Full access (Super Admin)';
        } elseif ($isLegacy) {
            $accessMode = 'full_legacy';
            $accessLabel = 'Full access (legacy admin account)';
        } else {
            $accessMode = 'scoped';
            $accessLabel = 'Scoped by assigned roles and permissions';
        }

        $roles = [];
        $permissionMeta = []; // key => ['label','module','action','sources'=>[]]

        if (Schema::hasTable('admin_role_assignments') && Schema::hasTable('roles')) {
            $assignedRoles = DB::table('admin_role_assignments as ara')
                ->join('roles as r', 'r.id', '=', 'ara.role_id')
                ->where('ara.admin_id', $adminId)
                ->orderBy('r.name')
                ->get(['r.id', 'r.name', 'r.slug']);

            foreach ($assignedRoles as $role) {
                $roleId = (int) $role->id;
                $rolePerms = [];
                if (Schema::hasTable('role_permissions') && Schema::hasTable('permissions')) {
                    $rolePerms = DB::table('role_permissions as rp')
                        ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                        ->where('rp.role_id', $roleId)
                        ->orderBy('p.module')
                        ->orderBy('p.permission_key')
                        ->get(['p.permission_key', 'p.label', 'p.module', 'p.action']);
                }

                $roles[] = [
                    'id' => $roleId,
                    'name' => (string) $role->name,
                    'slug' => (string) $role->slug,
                    'permission_count' => $rolePerms instanceof \Illuminate\Support\Collection ? $rolePerms->count() : count($rolePerms),
                ];

                foreach ($rolePerms as $perm) {
                    $key = (string) $perm->permission_key;
                    if (! isset($permissionMeta[$key])) {
                        $permissionMeta[$key] = [
                            'permission_key' => $key,
                            'label' => (string) ($perm->label ?: $key),
                            'module' => (string) $perm->module,
                            'action' => (string) $perm->action,
                            'sources' => [],
                        ];
                    }
                    $permissionMeta[$key]['sources'][] = (string) $role->name;
                }
            }
        }

        if (Schema::hasTable('admin_permissions') && Schema::hasTable('permissions')) {
            $direct = DB::table('admin_permissions as ap')
                ->join('permissions as p', 'p.id', '=', 'ap.permission_id')
                ->where('ap.admin_id', $adminId)
                ->orderBy('p.module')
                ->orderBy('p.permission_key')
                ->get(['p.permission_key', 'p.label', 'p.module', 'p.action']);

            foreach ($direct as $perm) {
                $key = (string) $perm->permission_key;
                if (! isset($permissionMeta[$key])) {
                    $permissionMeta[$key] = [
                        'permission_key' => $key,
                        'label' => (string) ($perm->label ?: $key),
                        'module' => (string) $perm->module,
                        'action' => (string) $perm->action,
                        'sources' => [],
                    ];
                }
                $permissionMeta[$key]['sources'][] = 'Direct grant';
            }
        }

        // Include any keys from RbacReadService that might come from legacy JSON roles.
        foreach ($rbac->permissionKeys($model) as $key) {
            if ($key === '*') {
                continue;
            }
            if (! isset($permissionMeta[$key])) {
                $parts = explode('.', $key, 2);
                $permissionMeta[$key] = [
                    'permission_key' => $key,
                    'label' => $key,
                    'module' => $parts[0] ?? 'other',
                    'action' => $parts[1] ?? '',
                    'sources' => ['Legacy / other'],
                ];
            }
        }

        ksort($permissionMeta);

        $modules = [];
        foreach ($permissionMeta as $item) {
            $moduleKey = $item['module'] !== '' ? $item['module'] : 'other';
            if (! isset($modules[$moduleKey])) {
                $modules[$moduleKey] = [
                    'key' => $moduleKey,
                    'label' => ucwords(str_replace('_', ' ', $moduleKey)),
                    'permissions' => [],
                ];
            }
            $item['sources'] = array_values(array_unique($item['sources']));
            $modules[$moduleKey]['permissions'][] = $item;
        }

        ksort($modules);

        return [
            'managedAdmin' => $admin,
            'access_mode' => $accessMode,
            'access_label' => $accessLabel,
            'roles' => $roles,
            'permission_count' => count($permissionMeta),
            'modules' => array_values($modules),
        ];
    }

    /**
     * Suspend or reactivate an administrator account.
     *
     * @return array<string, mixed>
     */
    public function setAccountStatus(int $id, string $status, int $actorId): array
    {
        $admin = $this->getAdmin($id);
        if ($admin === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot change the status of your own account.');
        }
        if ($this->isSuperAdminRow($admin)) {
            throw new InvalidArgumentException('Super Admin accounts cannot be suspended.');
        }

        $status = $this->normalizeStatus($status);
        if (! in_array($status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Only active or suspended status can be set from this action.');
        }

        $update = [
            'account_status' => $status,
            'is_active' => $status === 'active' ? 1 : 0,
            'locked_at' => null,
            'updated_at' => now(),
        ];

        DB::table('admins')->where('id', $id)->update($update);

        if ($status === 'suspended' && Schema::hasTable('admin_sessions')) {
            DB::table('admin_sessions')->where('admin_id', $id)->delete();
        }

        $label = $status === 'suspended' ? 'suspended' : 'reactivated';
        $this->audit(
            $status === 'suspended' ? 'admin_suspended' : 'admin_reactivated',
            'Administrator '.$label.': '.(string) $admin['email'],
            $actorId,
            $status === 'suspended' ? 'warning' : 'info',
        );

        return $this->getAdmin($id) ?? [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatAdmin(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        $roleIds = [];
        $roleNames = [];

        if ($id > 0 && Schema::hasTable('admin_role_assignments') && Schema::hasTable('roles')) {
            $assigned = DB::table('admin_role_assignments as ara')
                ->join('roles as r', 'r.id', '=', 'ara.role_id')
                ->where('ara.admin_id', $id)
                ->orderBy('r.name')
                ->get(['r.id', 'r.name', 'r.slug']);
            $roleIds = $assigned->pluck('id')->map(fn ($v) => (int) $v)->all();
            $roleNames = $assigned->pluck('name')->map(fn ($v) => (string) $v)->all();
        }

        return [
            'id' => $id,
            'full_name' => (string) ($row['full_name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'phone' => $row['phone'] ?? null,
            'department' => $row['department'] ?? null,
            'position' => $row['position'] ?? null,
            'role' => (string) ($row['role'] ?? 'admin'),
            'role_id' => isset($row['role_id']) ? (int) $row['role_id'] : null,
            'is_active' => ! empty($row['is_active']),
            'account_status' => (string) ($row['account_status'] ?? 'active'),
            'force_password_change' => ! empty($row['force_password_change']),
            'locked_at' => $row['locked_at'] ?? null,
            'recovery_email' => $row['recovery_email'] ?? null,
            'recovery_phone' => $row['recovery_phone'] ?? null,
            'last_login_at' => $row['last_login_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'role_ids' => $roleIds,
            'role_names' => $roleNames,
            'is_super_admin' => $this->isSuperAdminRow([
                'role' => (string) ($row['role'] ?? ''),
                'role_names' => $roleNames,
                'role_ids' => $roleIds,
            ] + (isset($row['role_slug']) ? ['role_slug' => $row['role_slug']] : [])),
        ];
    }

    /** @param  array<string, mixed>  $admin */
    /**
     * Finance-scoped roles should share the CMS password with Financial ERP login.
     *
     * @param  array{role?: string, role_id?: int|null, slug?: string|null}  $selection
     */
    private function shouldSyncErpPassword(array $selection): bool
    {
        $slug = strtolower(trim((string) ($selection['slug'] ?? '')));
        $catalog = [
            'finance_administrator',
            'treasurer',
            'accountant',
            'cashier',
            'payroll_officer',
            'auditor',
            'read_only_auditor',
        ];
        if ($slug !== '' && in_array($slug, $catalog, true)) {
            return true;
        }

        $roleId = (int) ($selection['role_id'] ?? 0);
        if ($roleId < 1 || ! Schema::hasTable('roles')) {
            return false;
        }

        $row = DB::table('roles')->where('id', $roleId)->first(['slug', 'dashboard_type']);
        if ($row === null) {
            return false;
        }

        if ((string) ($row->dashboard_type ?? '') === 'finance_admin') {
            return true;
        }

        $rowSlug = strtolower(trim((string) ($row->slug ?? '')));

        return in_array($rowSlug, $catalog, true);
    }

    private function isSuperAdminRow(array $admin): bool
    {
        if ((string) ($admin['role'] ?? '') === 'super_admin') {
            return true;
        }

        foreach ($admin['role_names'] ?? [] as $name) {
            if (strtolower((string) $name) === 'super admin' || strtolower((string) $name) === 'super_admin') {
                return true;
            }
        }

        if (! empty($admin['role_ids']) && Schema::hasTable('roles')) {
            return DB::table('roles')
                ->whereIn('id', $admin['role_ids'])
                ->where('slug', 'super_admin')
                ->exists();
        }

        return false;
    }

    private function normalizeLegacyRole(string $role, bool $allowSuper): string
    {
        $role = trim($role);
        if ($role === '' || ! in_array($role, self::LEGACY_ROLES, true)) {
            return 'admin';
        }
        if ($role === 'super_admin' && ! $allowSuper) {
            return 'admin';
        }

        return $role;
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);

        return in_array($status, self::ACCOUNT_STATUSES, true) ? $status : 'active';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function audit(string $event, string $message, int $actorId, string $severity = 'info'): void
    {
        if (! Schema::hasTable('security_logs') || $actorId < 1) {
            return;
        }

        DB::table('security_logs')->insert([
            'event_type' => $event,
            'severity' => $severity,
            'admin_id' => $actorId,
            'device_fingerprint' => null,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'message' => $message,
            'metadata' => null,
            'created_at' => now(),
        ]);
    }
}
