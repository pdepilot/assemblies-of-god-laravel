<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'member_code' => 'AGCI-'.fake()->unique()->numerify('#####'),
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $first.' '.$last,
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->numerify('080########'),
            'gender' => 'unspecified',
            'marital_status' => 'unspecified',
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'department' => 'Member',
            'status' => 'active',
            'joined_date' => now()->toDateString(),
        ];
    }
}
