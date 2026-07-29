<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyticsPolicy
{
    private const VIEW_TRAFFIC_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'content_editor',
    ];

    private const MANAGE_TRAFFIC_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'content_editor',
    ];

    private const VIEW_REPORTS_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'finance',
        'events_administrator',
        'content_editor',
        'communications_officer',
    ];

    private const EXPORT_REPORTS_ROLES = [
        'super_admin',
        'admin',
        'church_administrator',
        'finance',
        'events_administrator',
        'content_editor',
        'communications_officer',
    ];

    public function viewTraffic(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_TRAFFIC_ROLES, true);
    }

    public function manageTraffic(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::MANAGE_TRAFFIC_ROLES, true);
    }

    public function viewReports(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::VIEW_REPORTS_ROLES, true);
    }

    public function exportReports(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::EXPORT_REPORTS_ROLES, true);
    }

    public function requireViewTraffic(Admin $admin): void
    {
        if (! $this->viewTraffic($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageTraffic(Admin $admin): void
    {
        if (! $this->manageTraffic($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireViewReports(Admin $admin): void
    {
        if (! $this->viewReports($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireExportReports(Admin $admin): void
    {
        if (! $this->exportReports($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
