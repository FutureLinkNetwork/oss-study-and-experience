<?php

namespace Database\Factories;

use App\Models\Subdomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminDownload>
 */
class AdminDownloadFactory extends Factory
{
    protected $model = \App\Models\AdminDownload::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exportedAt = fake()->dateTimeBetween('-1 year', 'now');
        $dateStr = $exportedAt->format('Y-m-d');

        return [
            'subdomain_id' => Subdomain::factory(),
            'exported_at' => $exportedAt,
            'summary' => $dateStr.' 0時時点 利用者CSV 全件',
            's3_key' => 'subdomain_1/beneficiary_exports/'.$dateStr.'.csv',
            'download_type' => 'beneficiary',
        ];
    }
}
