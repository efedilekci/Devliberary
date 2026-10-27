<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->unique()->url(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'favicon_url' => '',
            'preview_image_url' => '',
            'category' => '',
            'notes' => '',
            'tags' => [],
            'is_favorite' => false,
            'is_archived' => false,
            'show_on_home' => true,
        ];
    }
}
