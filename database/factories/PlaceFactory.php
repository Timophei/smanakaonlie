<?php

namespace Database\Factories;

use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => 'hotel',
            'type' => 'house',
            'title' => 'Дом '.fake()->unique()->numberBetween(1, 9999),
            'path' => 'uslugi/gostinitsy/'.fake()->unique()->slug(2),
            'status' => 'published',
            'short_description' => fake()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
