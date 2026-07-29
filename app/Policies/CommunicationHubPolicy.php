<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class CommunicationHubPolicy
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
        'communications_officer',
    ];

    public function viewHub(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageHub(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_ROLES, true);
    }

    public function requireViewHub(Admin $admin): void
    {
        if (! $this->viewHub($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageHub(Admin $admin): void
    {
        if (! $this->manageHub($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
