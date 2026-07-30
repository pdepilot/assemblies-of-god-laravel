<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'password_hash' => Hash::make('password'),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'profile_photo' => null,
            'department' => 'youth',
            'position' => 'administrator',
            'ui_theme' => 'gold',
            'ui_mode' => 'dark',
            'role' => 'admin',
            'role_id' => null,
            'is_active' => true,
            'account_status' => 'active',
            'platform_access' => Admin::PLATFORM_BOTH,
            'force_password_change' => false,
            'locked_at' => null,
            'recovery_email' => fake()->safeEmail(),
            'recovery_phone' => null,
            'created_by' => null,
            'last_login_at' => null,
            'last_login_ip' => null,
            'remember_selector' => null,
            'remember_token_hash' => null,
            'remember_expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function agOnly(): static
    {
        return $this->state(fn (): array => ['platform_access' => Admin::PLATFORM_AG]);
    }

    public function sdtgOnly(): static
    {
        return $this->state(fn (): array => ['platform_access' => Admin::PLATFORM_SDTG]);
    }

    public function bothPlatforms(): static
    {
        return $this->state(fn (): array => ['platform_access' => Admin::PLATFORM_BOTH]);
    }
}
