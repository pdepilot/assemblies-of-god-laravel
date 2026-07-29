<?php

namespace Database\Factories;

use App\Models\RegistrationPortal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RegistrationPortal>
 */
class RegistrationPortalFactory extends Factory
{
    protected $model = RegistrationPortal::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Conference';

        return [
            'slug' => Str::slug($name),
            'event_name' => $name,
            'status' => 'draft',
            'registration_settings' => [
                'registration_type' => 'free',
                'confirmation_method' => 'automatic',
                'waiting_list' => false,
                'duplicate_email' => true,
                'duplicate_phone' => true,
                'enable_qr_code' => true,
                'enable_email_confirmation' => true,
                'enable_attendance' => true,
            ],
        ];
    }
}
