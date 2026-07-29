<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class MemberPolicy
{
    private const MEMBER_ADMIN_ROLES = ['super_admin', 'admin', 'church_administrator'];

    public function viewMembers(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MEMBER_ADMIN_ROLES, true);
    }

    public function manageMembers(Admin $admin): bool
    {
        return $this->viewMembers($admin);
    }

    public function requireViewMembers(Admin $admin): void
    {
        if (! $this->viewMembers($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageMembers(Admin $admin): void
    {
        if (! $this->manageMembers($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
