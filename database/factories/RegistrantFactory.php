<?php

namespace Database\Factories;

use App\Models\Registrant;
use App\Models\RegistrationPortal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registrant>
 */
class RegistrantFactory extends Factory
{
    protected $model = Registrant::class;

    public function definition(): array
    {
        return [
            'portal_id' => RegistrationPortal::factory(),
            'registration_number' => 'REG-'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('080########'),
            'status' => 'pending',
            'payment_status' => 'free',
            'attendance_status' => 'not_checked_in',
            'qr_token' => Str::random(32),
        ];
    }
}
