<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class FinancialErpPolicy
{
    private const VIEW_ROLES = ['super_admin', 'admin', 'church_administrator', 'finance'];

    public function viewErp(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageErp(Admin $admin): bool
    {
        return $this->viewErp($admin);
    }

    public function requireViewErp(Admin $admin): void
    {
        if (! $this->viewErp($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageErp(Admin $admin): void
    {
        if (! $this->manageErp($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
