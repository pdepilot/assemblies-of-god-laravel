<?php

namespace Database\Factories;

use App\Models\Pledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pledge>
 */
class PledgeFactory extends Factory
{
    protected $model = Pledge::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10000, 200000);

        return [
            'donor_name' => fake()->name(),
            'pledged_amount' => $amount,
            'amount_paid' => 0,
            'remaining_balance' => $amount,
            'start_date' => now()->toDateString(),
            'next_due_date' => now()->toDateString(),
            'status' => 'active',
        ];
    }
}
