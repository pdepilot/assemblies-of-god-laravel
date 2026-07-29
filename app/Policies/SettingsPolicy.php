<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

final class SettingsPolicy
{
    private const VIEW_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'communications_officer',
        'content_editor',
    ];

    private const MANAGE_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
    ];

    public function viewSettings(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageSettings(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_ROLES, true);
    }

    public function requireViewSettings(Admin $admin): void
    {
        if (! $this->viewSettings($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageSettings(Admin $admin): void
    {
        if (! $this->manageSettings($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageRbac(Admin $admin): void
    {
        if (! $this->manageRbac($admin)) {
            throw new AuthorizationException('Super Administrator access is required to manage roles.');
        }
    }

    public function manageRbac(Admin $admin): bool
    {
        return $admin->isSuperAdmin() || (string) $admin->role === 'super_admin';
    }
}
