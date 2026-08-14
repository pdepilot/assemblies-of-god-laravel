<?php

namespace App\Models;

use App\Notifications\AdminResetPasswordNotification;
use App\Services\Auth\RbacReadService;
use Database\Factories\AdminFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden([
    'password_hash',
    'remember_token_hash',
    'remember_selector',
])]
class Admin extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    /** @use HasFactory<AdminFactory> */
    use HasFactory;
    use Notifiable;

    public const PLATFORM_AG = 'ag';

    public const PLATFORM_BOTH = 'both';

    /** @var list<string> */
    public const PLATFORM_ACCESS_VALUES = [
        self::PLATFORM_AG,
        self::PLATFORM_BOTH,
    ];

    protected $table = 'admins';

    protected $primaryKey = 'id';

    protected $authPasswordName = 'password_hash';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'username',
        'full_name',
        'phone',
        'profile_photo',
        'department',
        'position',
        'ui_theme',
        'ui_mode',
        'role',
        'role_id',
        'is_active',
        'account_status',
        'platform_access',
        'force_password_change',
        'locked_at',
        'recovery_email',
        'recovery_phone',
        'created_by',
        'last_login_at',
        'last_login_ip',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'locked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getEmailForPasswordReset(): string
    {
        return (string) $this->email;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new AdminResetPasswordNotification($token));
    }

    /**
     * Prefer recovery email for reset mail when configured.
     */
    public function routeNotificationForMail(object $notification): string
    {
        if ($notification instanceof AdminResetPasswordNotification) {
            $recovery = trim((string) ($this->recovery_email ?? ''));
            if ($recovery !== '' && filter_var($recovery, FILTER_VALIDATE_EMAIL)) {
                return $recovery;
            }
        }

        return (string) $this->email;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->email;
    }

    public function canAccessPlatform(string $platform): bool
    {
        $access = strtolower(trim((string) ($this->platform_access ?? self::PLATFORM_BOTH)));
        if ($access === '' || $access === self::PLATFORM_BOTH) {
            return true;
        }

        // Legacy "sdtg" rows cannot access this AG-only app.
        if ($access === 'sdtg') {
            return false;
        }

        return $access === strtolower(trim($platform));
    }

    public function isAgOnly(): bool
    {
        return strtolower(trim((string) ($this->platform_access ?? ''))) === self::PLATFORM_AG;
    }

    public function canPermission(string $permission): bool
    {
        return app(RbacReadService::class)->can($this, $permission);
    }

    public function isSuperAdmin(): bool
    {
        return app(RbacReadService::class)->isSuperAdmin($this);
    }
}
