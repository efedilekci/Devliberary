<?php

namespace Database\Factories;

use App\Models\Snippet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Snippet>
 */
class SnippetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'code' => "echo 'hoi';",
            'language' => 'text',
            'description' => '',
            'tags' => [],
            'is_favorite' => false,
        ];
    }
}
