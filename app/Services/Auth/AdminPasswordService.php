<?php

namespace App\Services\Auth;

use App\Models\Admin;

final class AdminPasswordService
{
    public function verify(Admin $admin, string $plain): bool
    {
        $hash = $admin->getAuthPassword();

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        return password_verify($plain, $hash);
    }
}
