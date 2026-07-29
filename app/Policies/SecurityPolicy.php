<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

final class SecurityPolicy
{
    private const VIEW_ROLES = ['super_admin', 'admin', 'church_administrator'];

    private const MANAGE_ROLES = ['super_admin', 'admin'];

    public function viewSecurity(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageBans(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_ROLES, true);
    }

    public function requireViewSecurity(Admin $admin): void
    {
        if (! $this->viewSecurity($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageBans(Admin $admin): void
    {
        if (! $this->manageBans($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
