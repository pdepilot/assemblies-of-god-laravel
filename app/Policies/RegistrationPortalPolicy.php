<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class RegistrationPortalPolicy
{
    private const ADMIN_ROLES = ['super_admin', 'admin', 'church_administrator'];

    public function viewPortals(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::ADMIN_ROLES, true);
    }

    public function managePortals(Admin $admin): bool
    {
        return $this->viewPortals($admin);
    }

    public function requireViewPortals(Admin $admin): void
    {
        if (! $this->viewPortals($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManagePortals(Admin $admin): void
    {
        if (! $this->managePortals($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
