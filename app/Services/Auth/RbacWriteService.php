<?php

namespace App\Services\Auth;

use App\Support\RbacPlatform;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RbacWriteService
{
    /** @return list<array{value: string, label: string}> */
    public function dashboardTypes(?string $platform = null): array
    {
        $all = [
            ['value' => 'super_admin', 'label' => 'Super Admin Dashboard'],
            ['value' => 'church_admin', 'label' => 'Church Dashboard'],
            ['value' => 'youth', 'label' => 'Youth Dashboard'],
            ['value' => 'teens', 'label' => 'Teen Ministry Dashboard'],
            ['value' => 'children', 'label' => 'Children Ministry Dashboard'],
            ['value' => 'men', 'label' => "Men's Ministry Dashboard"],
            ['value' => 'women', 'label' => "Women's Ministry Dashboard"],
            ['value' => 'widowers', 'label' => 'Widowers Ministry Dashboard'],
            ['value' => 'widows', 'label' => 'Widows Ministry Dashboard'],
            ['value' => 'music', 'label' => 'Music Ministry Dashboard'],
            ['value' => 'choir', 'label' => 'Choir Dashboard'],
            ['value' => 'ushering', 'label' => 'Ushering Dashboard'],
            ['value' => 'sunday_school', 'label' => 'Sunday School Dashboard'],
            ['value' => 'finance', 'label' => 'Finance Dashboard'],
            ['value' => 'media', 'label' => 'Media Dashboard'],
            ['value' => 'events', 'label' => 'Events Dashboard'],
            ['value' => 'communications', 'label' => 'Communications Dashboard'],
        ];

        $platform = $platform !== null ? RbacPlatform::normalize($platform, RbacPlatform::AG) : null;
        if ($platform === RbacPlatform::AG || $platform === null) {
            return $all;
        }

        return $all;
    }

    /** @return list<array<string, mixed>> */
    public function listRoles(?string $platform = null): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }

        $platform = $platform !== null ? RbacPlatform::normalize($platform, RbacPlatform::BOTH) : null;

        $query = DB::table('roles as r')
            ->leftJoin('admin_role_assignments as ara', 'ara.role_id', '=', 'r.id')
            ->leftJoin('role_permissions as rp', 'rp.role_id', '=', 'r.id');

        if ($platform !== null && Schema::hasColumn('roles', 'platform')) {
            $query->where(function ($inner) use ($platform): void {
                $inner->where('r.platform', RbacPlatform::BOTH)
                    ->orWhere('r.platform', $platform)
                    ->orWhereNull('r.platform');
            });
        }

        $groupBy = ['r.id', 'r.slug', 'r.name', 'r.description', 'r.dashboard_type', 'r.is_system', 'r.is_active', 'r.created_at', 'r.updated_at'];
        if (Schema::hasColumn('roles', 'platform')) {
            $groupBy[] = 'r.platform';
        }

        return $query
            ->groupBy($groupBy)
            ->orderByDesc('r.is_system')
            ->orderBy('r.name')
            ->select([
                'r.*',
                DB::raw('COUNT(DISTINCT ara.admin_id) as admin_count'),
                DB::raw('COUNT(DISTINCT rp.permission_id) as permission_count'),
            ])
            ->get()
            ->map(function ($row) {
                $role = (array) $row;
                $role['id'] = (int) $row->id;
                $role['is_system'] = (bool) $row->is_system;
                $role['is_active'] = (bool) $row->is_active;
                $role['admin_count'] = (int) $row->admin_count;
                $role['permission_count'] = (int) $row->permission_count;
                $role['platform'] = RbacPlatform::normalize((string) ($row->platform ?? RbacPlatform::BOTH));
                $role['dashboard_label'] = $this->dashboardLabel((string) ($row->dashboard_type ?? ''));

                return $role;
            })
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getRole(int $id): ?array
    {
        if (! Schema::hasTable('roles') || $id < 1) {
            return null;
        }

        $row = DB::table('roles')->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        $role = (array) $row;
        $role['id'] = (int) $row->id;
        $role['is_system'] = (bool) $row->is_system;
        $role['is_active'] = (bool) $row->is_active;
        $role['platform'] = RbacPlatform::normalize((string) ($row->platform ?? RbacPlatform::BOTH));
        $role['permission_ids'] = Schema::hasTable('role_permissions')
            ? DB::table('role_permissions')->where('role_id', $id)->pluck('permission_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $role['dashboard_label'] = $this->dashboardLabel((string) ($row->dashboard_type ?? ''));

        return $role;
    }

    /**
     * @return array{items: list<array<string, mixed>>, modules: list<array<string, mixed>>}
     */
    public function listPermissionsGrouped(?string $platform = null): array
    {
        if (! Schema::hasTable('permissions')) {
            return ['items' => [], 'modules' => []];
        }

        $items = DB::table('permissions')
            ->orderBy('module')
            ->orderBy('permission_key')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'module' => (string) $row->module,
                'action' => (string) $row->action,
                'permission_key' => (string) $row->permission_key,
                'label' => (string) $row->label,
                'description' => (string) ($row->description ?? ''),
            ])
            ->all();

        $platform = $platform !== null ? RbacPlatform::normalize($platform, RbacPlatform::AG) : null;
        if ($platform === RbacPlatform::AG || $platform === null) {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => $item['module'] !== 'sdtg'
                    && ! str_starts_with($item['permission_key'], 'sdtg.')
            ));
        }

        $modules = [];
        foreach ($items as $item) {
            $key = $item['module'];
            if (! isset($modules[$key])) {
                $modules[$key] = [
                    'key' => $key,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'permissions' => [],
                ];
            }
            $modules[$key]['permissions'][] = $item;
        }

        return [
            'items' => $items,
            'modules' => array_values($modules),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listAdminsForAssignment(?string $platform = null): array
    {
        if (! Schema::hasTable('admins')) {
            return [];
        }

        $platform = $platform !== null ? RbacPlatform::normalize($platform, RbacPlatform::BOTH) : null;

        $query = DB::table('admins')->where('is_active', 1)->orderBy('full_name');
        $columns = ['id', 'full_name', 'email', 'role'];
        if (Schema::hasColumn('admins', 'platform_access')) {
            $columns[] = 'platform_access';
            if ($platform !== null) {
                $query->where(function ($inner) use ($platform): void {
                    $inner->where('platform_access', RbacPlatform::BOTH)
                        ->orWhere('platform_access', $platform)
                        ->orWhereNull('platform_access');
                });
            }
        }

        return $query
            ->get($columns)
            ->map(function ($row) {
                $roleIds = Schema::hasTable('admin_role_assignments')
                    ? DB::table('admin_role_assignments')->where('admin_id', $row->id)->pluck('role_id')->map(fn ($id) => (int) $id)->all()
                    : [];

                return [
                    'id' => (int) $row->id,
                    'full_name' => (string) $row->full_name,
                    'email' => (string) $row->email,
                    'role' => (string) $row->role,
                    'platform_access' => RbacPlatform::normalize((string) ($row->platform_access ?? RbacPlatform::BOTH)),
                    'role_ids' => $roleIds,
                    'role_names' => $this->roleNamesForIds($roleIds),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $permissionIds
     * @return array<string, mixed>
     */
    public function saveRole(array $data, array $permissionIds, int $actorId): array
    {
        if (! Schema::hasTable('roles')) {
            throw new InvalidArgumentException('Roles storage is unavailable.');
        }

        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $dashboardType = trim((string) ($data['dashboard_type'] ?? 'church_admin'));
        $platform = RbacPlatform::normalize((string) ($data['platform'] ?? RbacPlatform::AG), RbacPlatform::AG);
        $isActive = ! array_key_exists('is_active', $data) || ! empty($data['is_active']);

        if ($name === '') {
            throw new InvalidArgumentException('Role name is required.');
        }

        if ($slug === '') {
            $slug = Str::slug($name, '_');
        }
        $slug = Str::lower($slug);

        if (! preg_match('/^[a-z][a-z0-9_]{1,62}$/', $slug)) {
            throw new InvalidArgumentException('Role slug must use lowercase letters, numbers, and underscores.');
        }

        $validDashboards = array_column($this->dashboardTypes($platform === RbacPlatform::BOTH ? null : $platform), 'value');
        if (! in_array($dashboardType, array_column($this->dashboardTypes(), 'value'), true)) {
            throw new InvalidArgumentException('Invalid dashboard type selected.');
        }
        if ($validDashboards !== [] && ! in_array($dashboardType, $validDashboards, true) && $platform !== RbacPlatform::BOTH) {
            throw new InvalidArgumentException('Dashboard type is not valid for the selected platform.');
        }

        if ($slug === 'super_admin') {
            $platform = RbacPlatform::BOTH;
        }

        if ($id > 0) {
            $existing = $this->getRole($id);
            if ($existing === null) {
                throw new InvalidArgumentException('Role not found.');
            }
            if (($existing['slug'] ?? '') === 'super_admin' && $slug !== 'super_admin') {
                throw new InvalidArgumentException('The Super Admin role slug cannot be changed.');
            }
            if (($existing['slug'] ?? '') === 'super_admin') {
                $platform = RbacPlatform::BOTH;
            }

            $update = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description !== '' ? $description : null,
                'dashboard_type' => $dashboardType,
                'is_active' => $isActive,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('roles', 'platform')) {
                $update['platform'] = $platform;
            }

            DB::table('roles')->where('id', $id)->update($update);
            $this->syncRolePermissions($id, $permissionIds);
            $this->audit('rbac_role_updated', 'RBAC role updated: '.$name, $actorId);

            return $this->getRole($id) ?? [];
        }

        if (DB::table('roles')->where('slug', $slug)->exists()) {
            throw new InvalidArgumentException('A role with this slug already exists.');
        }

        $insert = [
            'slug' => $slug,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'dashboard_type' => $dashboardType,
            'is_system' => false,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('roles', 'platform')) {
            $insert['platform'] = $platform;
        }

        $id = (int) DB::table('roles')->insertGetId($insert);
        $this->syncRolePermissions($id, $permissionIds);
        $this->audit('rbac_role_created', 'RBAC role created: '.$name, $actorId);

        return $this->getRole($id) ?? [];
    }

    public function deleteRole(int $id, int $actorId): void
    {
        $role = $this->getRole($id);
        if ($role === null) {
            throw new InvalidArgumentException('Role not found.');
        }
        if (! empty($role['is_system'])) {
            throw new InvalidArgumentException('System roles cannot be deleted.');
        }

        DB::table('roles')->where('id', $id)->delete();
        $this->audit('rbac_role_deleted', 'RBAC role deleted: '.($role['name'] ?? ''), $actorId, 'warning');
    }

    /**
     * @param  list<int>  $roleIds
     * @param  string|null  $platformScope  When set to ag, only replace AG-scoped roles.
     */
    public function syncAdminRoles(int $adminId, array $roleIds, int $actorId, ?string $platformScope = null): void
    {
        if (! Schema::hasTable('admin_role_assignments') || ! Schema::hasTable('admins')) {
            throw new InvalidArgumentException('Role assignment storage is unavailable.');
        }

        $admin = DB::table('admins')->where('id', $adminId)->first();
        if ($adminId < 1 || $admin === null) {
            throw new InvalidArgumentException('Administrator not found.');
        }

        $adminAccess = RbacPlatform::normalize((string) ($admin->platform_access ?? RbacPlatform::AG));
        $platformScope = $platformScope !== null
            ? RbacPlatform::normalize($platformScope, RbacPlatform::AG)
            : null;

        $desired = array_values(array_unique(array_filter(array_map('intval', $roleIds), static fn (int $id): bool => $id > 0)));
        $roleRows = $desired === []
            ? collect()
            : DB::table('roles')->whereIn('id', $desired)->get(['id', 'name', 'platform']);
        $validRoleIds = [];
        foreach ($roleRows as $roleRow) {
            $rolePlatform = RbacPlatform::normalize((string) ($roleRow->platform ?? RbacPlatform::AG));
            if (! RbacPlatform::adminCanReceiveRole($adminAccess, $rolePlatform)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot assign role "%s" (%s) to an administrator with platform access "%s".',
                    (string) $roleRow->name,
                    $rolePlatform,
                    $adminAccess
                ));
            }
            $validRoleIds[] = (int) $roleRow->id;
        }
        $desired = array_values(array_intersect($desired, $validRoleIds));

        $current = DB::table('admin_role_assignments')
            ->where('admin_id', $adminId)
            ->pluck('role_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toRemove = array_diff($current, $desired);
        $toAdd = array_diff($desired, $current);

        if ($toRemove !== []) {
            DB::table('admin_role_assignments')
                ->where('admin_id', $adminId)
                ->whereIn('role_id', $toRemove)
                ->delete();
        }

        foreach ($toAdd as $roleId) {
            DB::table('admin_role_assignments')->insert([
                'admin_id' => $adminId,
                'role_id' => $roleId,
                'assigned_by' => $actorId > 0 ? $actorId : null,
                'assigned_at' => now(),
            ]);
        }

        if ($toRemove !== [] || $toAdd !== []) {
            $this->audit(
                'rbac_admin_roles_synced',
                sprintf('RBAC roles synced for admin #%d (%d roles)', $adminId, count($desired)),
                $actorId
            );
        }
    }

    /** @param list<int> $permissionIds */
    private function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $desired = array_values(array_unique(array_filter(array_map('intval', $permissionIds), static fn (int $id): bool => $id > 0)));
        if (Schema::hasTable('permissions') && $desired !== []) {
            $desired = DB::table('permissions')->whereIn('id', $desired)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        foreach ($desired as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'granted_at' => now(),
            ]);
        }
    }

    /** @param list<int> $roleIds */
    private function roleNamesForIds(array $roleIds): array
    {
        if ($roleIds === [] || ! Schema::hasTable('roles')) {
            return [];
        }

        return DB::table('roles')
            ->whereIn('id', $roleIds)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    private function dashboardLabel(string $value): string
    {
        foreach ($this->dashboardTypes() as $type) {
            if ($type['value'] === $value) {
                return $type['label'];
            }
        }

        return $value !== '' ? ucwords(str_replace('_', ' ', $value)) : 'Default';
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
