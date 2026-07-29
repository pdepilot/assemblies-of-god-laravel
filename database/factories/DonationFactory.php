<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'donation_code' => 'D'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'donor_name' => fake()->name(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'currency' => 'NGN',
            'category' => 'offering',
            'fund_scope' => 'church',
            'payment_method' => 'cash',
            'payment_provider' => 'manual',
            'payment_status' => 'successful',
            'donation_date' => now()->toDateString(),
        ];
    }
}
