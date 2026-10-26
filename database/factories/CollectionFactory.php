<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'description' => '',
            'icon' => 'folder',
            'accent_color' => '#F97316',
            'position' => 0,
            'is_pinned_to_home' => true,
            'is_collapsed' => false,
        ];
    }
}
