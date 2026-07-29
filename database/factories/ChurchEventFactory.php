<?php

namespace Database\Factories;

use App\Models\ChurchEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchEvent>
 */
class ChurchEventFactory extends Factory
{
    protected $model = ChurchEvent::class;

    public function definition(): array
    {
        return [
            'event_code' => 'EVT'.strtoupper(fake()->unique()->bothify('######')),
            'title' => fake()->sentence(3),
            'event_date' => now()->addWeek()->toDateString(),
            'category' => 'program',
            'image_path' => 'img/events-1.jpg',
            'icon_class' => 'fa-church',
            'is_published' => true,
            'status' => 'upcoming',
        ];
    }
}
