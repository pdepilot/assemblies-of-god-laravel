<?php

namespace App\Support;

use App\Models\Admin;

/**
 * Platform scoping for shared RBAC (AG church CMS only after SDTG extraction).
 */
final class RbacPlatform
{
    public const AG = 'ag';

    /** @deprecated Kept for normalizing legacy DB rows only. */
    public const SDTG = 'sdtg';

    public const BOTH = 'both';

    /** @var list<string> */
    public const VALUES = [self::AG, self::SDTG, self::BOTH];

    public static function normalize(?string $value, string $default = self::AG): string
    {
        $value = strtolower(trim((string) $value));

        // SDTG was extracted; treat leftover sdtg/both as AG for this app.
        if ($value === self::SDTG || $value === self::BOTH) {
            return self::AG;
        }

        return in_array($value, self::VALUES, true) ? $value : $default;
    }

    /**
     * Active product context for permission/role resolution.
     */
    public static function current(): string
    {
        return self::AG;
    }

    public static function roleMatchesContext(string $rolePlatform, ?string $context = null): bool
    {
        $context = self::normalize($context ?? self::current(), self::AG);
        $rolePlatform = self::normalize($rolePlatform, self::AG);

        return $rolePlatform === self::BOTH || $rolePlatform === $context || $rolePlatform === self::AG;
    }

    /**
     * Whether an admin's platform_access may receive a given role platform.
     */
    public static function adminCanReceiveRole(?string $adminAccess, string $rolePlatform): bool
    {
        $adminAccess = self::normalize($adminAccess, self::AG);
        $rolePlatform = self::normalize($rolePlatform, self::AG);

        if ($adminAccess === self::BOTH || $rolePlatform === self::BOTH) {
            return true;
        }

        return $adminAccess === $rolePlatform;
    }

    public static function authenticatedAdmin(): ?Admin
    {
        $user = auth('admin')->user();

        return $user instanceof Admin ? $user : null;
    }
}
