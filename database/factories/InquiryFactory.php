<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    /**
     * Define the model's default state.
     * subdomain_id, user_id, created_user_id は呼び出し側で指定すること。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquiry_type' => InquiryType::User,
            'content' => fake()->paragraph(),
            'status' => InquiryStatus::Pending,
            'remarks' => null,
            'updated_user_id' => null,
        ];
    }
}
