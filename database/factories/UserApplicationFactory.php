<?php

namespace Database\Factories;

use App\Models\Subdomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserApplication>
 */
class UserApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subdomain_id' => Subdomain::factory(),
            'certification_number' => fake()->numerify('CERT-####'),
            'guardian_name' => fake()->name(),
            'guardian_name_kana' => null,
            'guardian_birth_date' => fake()->date(),
            'guardian_address' => fake()->address(),
            'guardian_phone' => fake()->phoneNumber(),
            'guardian_email' => fake()->email(),
            'child_name' => fake()->name(),
            'child_name_kana' => null,
            'child_birth_date' => fake()->date(),
            'elementary_school_name' => fake()->company().'小学校',
            'grade' => fake()->randomElement(['1年生', '2年生', '3年生', '4年生', '5年生', '6年生']),
            'child_address' => fake()->address(),
            'child_address_same_as_guardian' => false,
            'child_registered_in_municipality_and_receiving_scholarship' => false,
            'survey_consent' => fake()->boolean(),
            'privacy_policy_agreed' => true,
            'classroom_name_1' => null,
            'classroom_location_1' => null,
            'classroom_phone_1' => null,
            'classroom_contact_person_1' => null,
            'classroom_name_2' => null,
            'classroom_location_2' => null,
            'classroom_phone_2' => null,
            'classroom_contact_person_2' => null,
            'classroom_name_3' => null,
            'classroom_location_3' => null,
            'classroom_phone_3' => null,
            'classroom_contact_person_3' => null,
            'document_s3_key' => null,
            'document_original_filename' => null,
            'document_file_size' => null,
            'document_mime_type' => null,
            'is_exported' => false,
            'is_excluded_from_download' => false,
            'admin_remarks' => null,
        ];
    }
}
