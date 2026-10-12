<?php

namespace Database\Factories;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => 'photos/'.fake()->unique()->slug(2).'.jpg',
            'alt' => fake()->sentence(4),
            'author_name' => fake()->name(),
            'author_url' => 'https://instagram.com/'.fake()->userName(),
            'is_published' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
