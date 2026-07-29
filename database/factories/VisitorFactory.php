<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    public function definition(): array
    {
        return [
            'visitor_code' => 'V'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 3, '0', STR_PAD_LEFT),
            'full_name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->numerify('080########'),
            'gender' => 'unspecified',
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'first_visit_date' => now()->toDateString(),
            'visit_count' => 1,
            'follow_up_status' => 'new',
        ];
    }
}
