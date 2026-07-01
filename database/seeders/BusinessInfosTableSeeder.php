<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessInfosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('business_infos')->delete();

        \DB::table('business_infos')->insert([
            0 => [
                'id' => 1,
                'user_id' => 3,
                'subdomain_id' => 1,
                'applicant_type' => 'individual',
                'business_name' => '株式会社フューチャーリンクネットワーク',
                'business_name_kana' => 'フューチャーリンクネットワーク',
                'representative_name' => '石井丈晴',
                'representative_name_kana' => 'イシイタケハル',
                'postal_code' => '273-0031',
                'prefecture' => '千葉県',
                'city' => '船橋市',
                'address1' => '西船橋4-19-3',
                'building_name' => '西船成島ビル8F',
                'phone' => '047-495-0525',
                'fax' => '047-495-0625',
                'email' => 'test_business@example.com',
                'website_url' => 'https://www.futurelink.co.jp/',
                'email_timing' => 'immediate',
                'contact_person' => '代表者名',
                'contact_phone' => '047-495-0525',
                'document_person' => '代表者名',
                'document_address' => '千葉県船橋市西船橋4-19-3 西船成島ビル8F',
                'business_hours' => '9:00-18:00',
                'holiday' => '土日祝日',
                'bank_code' => '0001',
                'branch_code' => '009',
                'account_type' => '普通',
                'account_number' => '123456789',
                'account_holder_name' => '口座名義',
                'apply' => 1,
                'is_active' => 1,
                'created_user' => 1,
                'updated_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }
}
