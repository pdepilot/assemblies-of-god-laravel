<?php

namespace Database\Factories;

use App\Models\CommitmentProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitmentProgram>
 */
class CommitmentProgramFactory extends Factory
{
    protected $model = CommitmentProgram::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Commitment',
            'target_amount' => fake()->randomFloat(2, 100000, 5000000),
            'status' => 'active',
        ];
    }
}
