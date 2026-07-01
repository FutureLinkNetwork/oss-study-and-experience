<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subdomain>
 */
class SubdomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subdomain' => fake()->unique()->domainWord(),
            'name' => fake()->company(),
            'system_name' => fake()->company().'システム',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'settings' => null,
        ];
    }
}
