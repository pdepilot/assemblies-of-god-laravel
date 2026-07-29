<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class VisitorPolicy
{
    private const VIEW_ROLES = ['super_admin', 'admin', 'church_administrator', 'follow_up_officer'];

    private const DELETE_ROLES = ['super_admin', 'admin', 'church_administrator'];

    public function viewVisitors(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageVisitors(Admin $admin): bool
    {
        return $this->viewVisitors($admin);
    }

    public function deleteVisitors(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::DELETE_ROLES, true);
    }

    public function requireViewVisitors(Admin $admin): void
    {
        if (! $this->viewVisitors($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageVisitors(Admin $admin): void
    {
        if (! $this->manageVisitors($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireDeleteVisitors(Admin $admin): void
    {
        if (! $this->deleteVisitors($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
