<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => 'devto',
            'external_id' => (string) $this->faker->unique()->numberBetween(1, 1000000),
            'title' => $this->faker->sentence(),
            'url' => $this->faker->url(),
            'author' => $this->faker->name(),
            'thumbnail_url' => null,
            'tags' => [],
            'raw_engagement' => [],
            'trending_score' => $this->faker->numberBetween(0, 100),
            'previous_trending_score' => 0,
            'published_at' => now(),
            'fetched_at' => now(),
        ];
    }
}
