<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Main (本番・マスタ用 - 常に実行)
        $this->call([
            \Database\Seeders\Main\SubdomainSeeder::class,
            \Database\Seeders\Main\RoleSeeder::class,
            \Database\Seeders\Main\UserSeeder::class,
            \Database\Seeders\Main\CourseCategorySeeder::class,
            \Database\Seeders\Main\BankBranchSeeder::class,
        ]);

        // Dev (開発・テスト用 - 本番以外で実行)
        if (! app()->environment('production')) {
            $this->call([
                \Database\Seeders\Dev\SubdomainSeeder::class,
                \Database\Seeders\Dev\UserSeeder::class,
                \Database\Seeders\Dev\NoticeSeeder::class,
                \Database\Seeders\BusinessInfosTableSeeder::class,
                \Database\Seeders\ClassroomInfosTableSeeder::class,
                \Database\Seeders\CourseInfosTableSeeder::class,
            ]);
        }
    }
}
