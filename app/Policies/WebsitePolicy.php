<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class WebsitePolicy
{
    private const VIEW_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'content_editor',
    ];

    private const MANAGE_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'content_editor',
    ];

    public function viewWebsite(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageWebsite(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_ROLES, true);
    }

    public function requireViewWebsite(Admin $admin): void
    {
        if (! $this->viewWebsite($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageWebsite(Admin $admin): void
    {
        if (! $this->manageWebsite($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
