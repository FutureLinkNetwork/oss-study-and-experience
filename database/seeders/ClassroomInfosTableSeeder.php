<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ClassroomInfosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('classroom_infos')->delete();

        \DB::table('classroom_infos')->insert([
            0 => [
                'id' => 1,
                'business_info_id' => 1,
                'classroom_name' => '教室名',
                'classroom_name_kana' => '教室名フリガナ',
                'classroom_representative_name' => '代表者名',
                'classroom_representative_name_kana' => '代表者名（カナ）',
                'classroom_postal_code' => '100-0001',
                'classroom_prefecture' => '東京都',
                'classroom_city' => '千代田区',
                'classroom_address1' => '千代田西船',
                'classroom_building_name' => '西船成島ビル',
                'classroom_latitude' => '34.78384898',
                'classroom_longitude' => '135.40350616',
                'use_map' => true,
                'classroom_phone' => '047-495-0525',
                'classroom_fax' => '047-495-0625',
                'classroom_email' => '',
                'business_hours' => '9:00-18:00',
                'holiday' => '土日祝日',
                'classroom_introduction' => '教室紹介',
                'service_type' => '教室型',
                'lesson_category' => 30,
                'lesson_category_other' => '',
                'classroom_image_original_filename' => '',
                'classroom_image_s3_key' => '',
                'classroom_image_file_size' => 0,
                'classroom_image_mime_type' => '',
                'classroom_image_thumbnail_s3_key' => '',
                'classroom_image_medium_s3_key' => '',
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
