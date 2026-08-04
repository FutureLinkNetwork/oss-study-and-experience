<?php

namespace Database\Factories;

use App\Models\CourseParentCategory;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseCategory>
 */
class CourseCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subdomain_id' => Subdomain::factory(),
            'parent_category_id' => CourseParentCategory::factory(),
            'name' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'created_user_id' => User::factory(),
            'updated_user_id' => User::factory(),
        ];
    }
}
