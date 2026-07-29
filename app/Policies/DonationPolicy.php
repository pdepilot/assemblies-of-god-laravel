<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class DonationPolicy
{
    private const VIEW_ROLES = ['super_admin', 'admin', 'church_administrator', 'finance'];

    public function viewDonations(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_ROLES, true);
    }

    public function manageDonations(Admin $admin): bool
    {
        return $this->viewDonations($admin);
    }

    public function requireViewDonations(Admin $admin): void
    {
        if (! $this->viewDonations($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageDonations(Admin $admin): void
    {
        if (! $this->manageDonations($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
