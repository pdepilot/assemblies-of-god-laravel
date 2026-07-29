<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class EventPolicy
{
    private const ADMIN_ROLES = ['super_admin', 'admin', 'church_administrator'];

    public function viewEvents(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::ADMIN_ROLES, true);
    }

    public function manageEvents(Admin $admin): bool
    {
        return $this->viewEvents($admin);
    }

    public function requireViewEvents(Admin $admin): void
    {
        if (! $this->viewEvents($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageEvents(Admin $admin): void
    {
        if (! $this->manageEvents($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
