<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subdomain_id' => null,
            'user_id' => null,
            'child_id' => fake()->numerify('CHILD-####'),
            'certification_number' => fake()->numerify('CERT-####'),
            'guardian_name' => fake()->name(),
            'guardian_birth_date' => fake()->date(),
            'guardian_address' => fake()->address(),
            'guardian_phone' => fake()->phoneNumber(),
            'guardian_email' => fake()->email(),
            'child_name' => fake()->name(),
            'child_birth_date' => fake()->date(),
            'elementary_school_name' => fake()->company().'小学校',
            'grade' => fake()->randomElement(['1年生', '2年生', '3年生', '4年生', '5年生', '6年生']),
            'child_address' => fake()->address(),
            'survey_consent' => fake()->boolean(),
            'classroom_name_1' => fake()->optional()->company().'教室',
            'classroom_location_1' => fake()->optional()->address(),
            'classroom_phone_1' => fake()->optional()->phoneNumber(),
            'classroom_contact_person_1' => fake()->optional()->name(),
            'classroom_name_2' => null,
            'classroom_location_2' => null,
            'classroom_phone_2' => null,
            'classroom_contact_person_2' => null,
            'classroom_name_3' => null,
            'classroom_location_3' => null,
            'classroom_phone_3' => null,
            'classroom_contact_person_3' => null,
            'application_date' => fake()->date(),
            'certification_date' => fake()->date(),
            'status' => '決定通知書未送信',
            'disqualification_date' => null,
            'labels' => null,
        ];
    }
}
