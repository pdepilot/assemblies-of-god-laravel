<?php

namespace App\Services\Auth;

use App\Models\Admin;
use App\Support\RbacPlatform;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RbacReadService
{
    /** @var array<string, list<string>> */
    private array $permissionCache = [];

    public function isSuperAdmin(Admin $admin): bool
    {
        if ((string) $admin->role === 'super_admin') {
            return true;
        }

        $slug = $this->resolvedRoleSlug($admin);

        if ($slug === 'super_admin') {
            return true;
        }

        return in_array('*', $this->permissionKeys($admin), true);
    }

    public function hasLegacyFullAccess(Admin $admin): bool
    {
        return (int) ($admin->role_id ?? 0) === 0
            && (string) $admin->role === 'admin'
            && ! $this->isSuperAdmin($admin);
    }

    public function can(Admin $admin, string $permission, ?string $platform = null): bool
    {
        if ($this->isSuperAdmin($admin) || $this->hasLegacyFullAccess($admin)) {
            return true;
        }

        $permissions = $this->permissionKeys($admin, $platform);

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        $parts = explode('.', $permission);
        if (count($parts) >= 2) {
            $moduleWildcard = $parts[0].'.*';

            return in_array($moduleWildcard, $permissions, true);
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(Admin $admin, ?string $platform = null): array
    {
        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);
        $cacheKey = (int) $admin->id.'|'.$platform;

        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        $keys = [];

        if (Schema::hasTable('admin_role_assignments') && Schema::hasTable('role_permissions')) {
            $keys = array_merge($keys, $this->normalizedRolePermissions((int) $admin->id, $platform));
        }

        if (Schema::hasTable('admin_permissions')) {
            $keys = array_merge($keys, $this->directAdminPermissions((int) $admin->id));
        }

        if ($keys === [] && Schema::hasTable('admin_roles') && (int) ($admin->role_id ?? 0) > 0) {
            $keys = array_merge($keys, $this->jsonRolePermissions((int) $admin->role_id));
        }

        if ($keys === [] && (string) $admin->role === 'super_admin') {
            $keys = ['*'];
        }

        $this->permissionCache[$cacheKey] = array_values(array_unique($keys));

        return $this->permissionCache[$cacheKey];
    }

    public function isRbacEnforcementEnabled(): bool
    {
        if (! Schema::hasTable('platform_setting_groups')) {
            return false;
        }

        $raw = DB::table('platform_setting_groups')
            ->where('group_key', 'rbac')
            ->value('settings');

        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $settings = json_decode($raw, true);

        return is_array($settings) && ! empty($settings['enforcement_enabled']);
    }

    private function resolvedRoleSlug(Admin $admin, ?string $platform = null): string
    {
        if (! Schema::hasTable('admin_role_assignments') || ! Schema::hasTable('roles')) {
            return (string) $admin->role;
        }

        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);

        $query = DB::table('admin_role_assignments as ara')
            ->join('roles as r', 'r.id', '=', 'ara.role_id')
            ->where('ara.admin_id', $admin->id)
            ->where('r.is_active', true);

        $this->applyRolePlatformFilter($query, $platform, 'r');

        $slug = $query
            ->orderByRaw("CASE WHEN r.slug = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('r.id')
            ->value('r.slug');

        return is_string($slug) && $slug !== '' ? $slug : (string) $admin->role;
    }

    /**
     * @return list<string>
     */
    private function normalizedRolePermissions(int $adminId, string $platform): array
    {
        $query = DB::table('admin_role_assignments as ara')
            ->join('roles as r', 'r.id', '=', 'ara.role_id')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'ara.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('ara.admin_id', $adminId)
            ->where('r.is_active', true);

        $this->applyRolePlatformFilter($query, $platform, 'r');

        return $query
            ->pluck('p.permission_key')
            ->map(static fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyRolePlatformFilter($query, string $platform, string $alias = 'r'): void
    {
        if (! Schema::hasColumn('roles', 'platform')) {
            return;
        }

        $query->where(function ($inner) use ($platform, $alias): void {
            $inner->where($alias.'.platform', RbacPlatform::BOTH)
                ->orWhere($alias.'.platform', $platform)
                ->orWhereNull($alias.'.platform');
        });
    }

    /**
     * @return list<string>
     */
    private function directAdminPermissions(int $adminId): array
    {
        return DB::table('admin_permissions as ap')
            ->join('permissions as p', 'p.id', '=', 'ap.permission_id')
            ->where('ap.admin_id', $adminId)
            ->pluck('p.permission_key')
            ->map(static fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function jsonRolePermissions(int $roleId): array
    {
        $raw = DB::table('admin_roles')->where('id', $roleId)->value('permissions');
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
