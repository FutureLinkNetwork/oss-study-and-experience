<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'display_name' => fake()->jobTitle(),
            'description' => fake()->optional()->sentence(),
            'is_global' => false,
            'level' => fake()->numberBetween(0, 100),
            'permissions' => null,
            'is_active' => true,
        ];
    }
}
