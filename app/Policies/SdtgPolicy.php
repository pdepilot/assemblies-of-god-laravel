<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class SdtgPolicy
{
    private const ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'sdtg_administrator',
        'event_administrator',
    ];

    public function viewSdtg(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::ROLES, true);
    }

    public function manageSdtg(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::ROLES, true);
    }

    public function requireViewSdtg(Admin $admin): void
    {
        if (! $this->viewSdtg($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageSdtg(Admin $admin): void
    {
        if (! $this->manageSdtg($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
