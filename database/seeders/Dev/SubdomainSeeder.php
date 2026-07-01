<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubdomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 本番環境では実行しない
        if (app()->environment('production')) {
            return;
        }

        $devSubdomains = [
            [
                'subdomain' => 'www',
                'name' => '子どもの習い事応援事業',
                'system_name' => '子どもの習い事応援事業',
                'description' => '子どもの習い事応援事業',
                'voucher_amount' => 8000,
                'voucher_expiry' => 0,
                'voucher_publish_date' => 1,
                'tax_rate' => 10,
                'is_active' => true,
                'settings' => [
                    'theme' => [
                        'primary_color' => '#3b82f6',
                        'secondary_color' => '#1e40af',
                    ],
                    'grades' => ['小学１年'],
                    'contact' => [
                        'email' => 'dev@fln.example.com',
                        'phone' => '000-0000-0000',
                    ],
                    'voucher_amount' => 5000,
                    'max_applications' => 3,
                    'application_period' => [
                        'start' => '2025-01-01',
                        'end' => '2025-12-31',
                    ],
                ],
                'latitude' => 34.64929936,
                'longitude' => 135.00149667,
                'postal_code' => '000-0000',
                'address' => '千葉県船橋市',
                'phone' => '000-000-0000',
                'fax' => '000-000-0000',
                'transfer_date_rule' => 'next_month_end',
                'zengin_requester_code' => '1234567890',
                'zengin_requester_name' => 'ﾅﾗｲｺﾞﾄ',
                'zengin_bank_code' => '0000',
                'zengin_bank_name' => 'ｴﾌｴﾙｴﾇ',
                'zengin_branch_code' => '000',
                'zengin_branch_name' => 'ﾆｼﾌﾅﾊﾞｼ',
                'zengin_account_type' => '1',
                'zengin_account_number' => '0000000',
            ],
        ];

        foreach ($devSubdomains as $subdomain) {
            DB::table('subdomains')->updateOrInsert(
                ['subdomain' => $subdomain['subdomain']],
                array_merge($subdomain, [
                    'settings' => json_encode($subdomain['settings']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
