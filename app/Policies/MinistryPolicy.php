<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class MinistryPolicy
{
    private const ADMIN_ROLES = ['super_admin', 'admin', 'church_administrator'];

    public function viewMinistries(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::ADMIN_ROLES, true);
    }

    public function manageMinistries(Admin $admin): bool
    {
        return $this->viewMinistries($admin);
    }

    public function manageSettings(Admin $admin): bool
    {
        return $this->viewMinistries($admin);
    }

    public function requireViewMinistries(Admin $admin): void
    {
        if (! $this->viewMinistries($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageMinistries(Admin $admin): void
    {
        if (! $this->manageMinistries($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageSettings(Admin $admin): void
    {
        if (! $this->manageSettings($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
