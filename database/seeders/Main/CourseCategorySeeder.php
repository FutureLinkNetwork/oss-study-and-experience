<?php

namespace Database\Seeders\Main;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // サブドメインと管理者ユーザーを取得
        $subdomainId = DB::table('subdomains')->where('subdomain', 'www')->value('id');
        $adminUserId = DB::table('users')
            ->where('subdomain_id', $subdomainId)
            ->where('login_id', 'admin')
            ->value('id');

        if (!$subdomainId || !$adminUserId) {
            $this->command->error('サブドメインまたは管理者ユーザーが見つかりません。先にSubdomainSeederとUserSeederを実行してください。');
            return;
        }

        // 親カテゴリ作成
        $artsParentId = $this->createParentCategory($subdomainId, $adminUserId, '文化・芸術活動', 1);
		$sportsParentId = $this->createParentCategory($subdomainId, $adminUserId, 'スポーツ活動', 2);
        $studyParentId = $this->createParentCategory($subdomainId, $adminUserId, '学習活動', 3);

        // 子カテゴリ作成（芸術・文化）
        $artsCategories = [
            'ピアノ','その他音楽','習字・書道','そろばん','パソコン・プログラミング、','英語塾・英会話教室','その他語学教室、','美術','調理','手芸','工作','その他文化・芸術'
        ];

        foreach ($artsCategories as $index => $categoryName) {
            $this->createCategory($subdomainId, $artsParentId, $adminUserId, $categoryName, $index + 1);
        }

		// 子カテゴリ作成（スポーツ・運動）
        $sportsCategories = [
            '水泳','ダンス','体操','バレエ','陸上','柔道','空手','剣道','サッカー・フットサル','バスケットボール','バレーボール','野球','テニス','卓球','バドミントン','ラグビー','その他スポーツ'
        ];

        foreach ($sportsCategories as $index => $categoryName) {
            $this->createCategory($subdomainId, $sportsParentId, $adminUserId, $categoryName, $index + 1);
        }

        // 子カテゴリ作成（学習・教養）
        $studyCategories = [
            '学習塾','家庭教師','オンライン学習塾','オンライン家庭教師','その他学習'
        ];

        foreach ($studyCategories as $index => $categoryName) {
            $this->createCategory($subdomainId, $studyParentId, $adminUserId, $categoryName, $index + 1);
        }

    }

    /**
     * 親カテゴリを作成
     */
    private function createParentCategory(int $subdomainId, int $userId, string $name, int $sortOrder): int
    {
        $existing = DB::table('course_categories_parent')
            ->where('subdomain_id', $subdomainId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('course_categories_parent')->insertGetId([
            'subdomain_id' => $subdomainId,
            'name' => $name,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_user_id' => $userId,
            'updated_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 子カテゴリを作成
     */
    private function createCategory(int $subdomainId, int $parentCategoryId, int $userId, string $name, int $sortOrder): void
    {
        $existing = DB::table('course_categories')
            ->where('subdomain_id', $subdomainId)
            ->where('parent_category_id', $parentCategoryId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            return;
        }

        DB::table('course_categories')->insert([
            'subdomain_id' => $subdomainId,
            'parent_category_id' => $parentCategoryId,
            'name' => $name,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_user_id' => $userId,
            'updated_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

