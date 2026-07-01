<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CourseInfosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('course_infos')->delete();
        
        \DB::table('course_infos')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_info_id' => 1,
                'classroom_info_id' => 1,
                'course_name' => 'サンプルコース',
                'course_description' => 'サンプルコースです。',
                'price' => 10000,
                'tax_type' => 'inclusive',
                'open_date' => now(),
                'end_date' => now(),
                'is_active' => 1,
                'created_user' => 1,
                'updated_user' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ),
        ));
        
        
    }
}