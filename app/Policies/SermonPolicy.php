<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class SermonPolicy
{
    private const VIEW_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'media_administrator',
    ];

    private const MANAGE_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'media_administrator',
    ];

    public function viewSermons(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageSermons(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_ROLES, true);
    }

    public function requireViewSermons(Admin $admin): void
    {
        if (! $this->viewSermons($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageSermons(Admin $admin): void
    {
        if (! $this->manageSermons($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
