<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;

class SundaySchoolPolicy
{
    private const SS_ADMIN_ROLES = ['super_admin', 'admin', 'ss_superintendent'];

    public function manageClasses(Admin $admin): bool
    {
        return in_array((string) $admin->role, self::SS_ADMIN_ROLES, true);
    }

    public function manageStudents(Admin $admin): bool
    {
        return in_array((string) $admin->role, [...self::SS_ADMIN_ROLES, 'ss_teacher'], true);
    }

    public function deleteStudents(Admin $admin): bool
    {
        return $this->manageClasses($admin);
    }

    public function manageTeachers(Admin $admin): bool
    {
        return $this->manageClasses($admin);
    }

    public function recordAttendance(Admin $admin): bool
    {
        return $this->manageStudents($admin);
    }

    public function manageOfferings(Admin $admin): bool
    {
        return $this->manageStudents($admin);
    }

    public function manageCurriculum(Admin $admin): bool
    {
        return $this->manageStudents($admin);
    }

    public function deleteCurriculum(Admin $admin): bool
    {
        return $this->manageClasses($admin);
    }

    public function viewReports(Admin $admin): bool
    {
        return $this->manageStudents($admin);
    }

    public function exportTeachers(Admin $admin): bool
    {
        return $this->manageClasses($admin);
    }

    public function viewAnalytics(Admin $admin): bool
    {
        return $this->manageStudents($admin);
    }

    public function viewAttendanceRemovals(Admin $admin): bool
    {
        return in_array((string) $admin->role, ['super_admin', 'ss_superintendent'], true);
    }

    public function requireManageClasses(Admin $admin): void
    {
        if (! $this->manageClasses($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageStudents(Admin $admin): void
    {
        if (! $this->manageStudents($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireDeleteStudents(Admin $admin): void
    {
        if (! $this->deleteStudents($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageTeachers(Admin $admin): void
    {
        if (! $this->manageTeachers($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireRecordAttendance(Admin $admin): void
    {
        if (! $this->recordAttendance($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageOfferings(Admin $admin): void
    {
        if (! $this->manageOfferings($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireManageCurriculum(Admin $admin): void
    {
        if (! $this->manageCurriculum($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireDeleteCurriculum(Admin $admin): void
    {
        if (! $this->deleteCurriculum($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireViewReports(Admin $admin): void
    {
        if (! $this->viewReports($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireExportTeachers(Admin $admin): void
    {
        if (! $this->exportTeachers($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireViewAnalytics(Admin $admin): void
    {
        if (! $this->viewAnalytics($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }

    public function requireViewAttendanceRemovals(Admin $admin): void
    {
        if (! $this->viewAttendanceRemovals($admin)) {
            throw new AuthorizationException('Access denied.');
        }
    }
}
