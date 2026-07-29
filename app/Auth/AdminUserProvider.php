<?php

namespace App\Auth;

use App\Models\Admin;
use App\Services\Auth\AdminPasswordService;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

final class AdminUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (! $user instanceof Admin) {
            return false;
        }

        return app(AdminPasswordService::class)->verify(
            $user,
            (string) ($credentials['password'] ?? ''),
        );
    }
}
