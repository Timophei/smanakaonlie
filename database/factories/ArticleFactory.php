<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Статья '.fake()->unique()->numberBetween(1, 9999),
            'path' => 'stati/'.fake()->unique()->slug(2),
            'status' => 'published',
            'lead' => fake()->sentence(),
            'content' => [
                ['type' => 'text', 'data' => ['body' => '<p>'.fake()->paragraph().'</p>']],
            ],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
