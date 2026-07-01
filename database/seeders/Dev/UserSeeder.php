<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        // サブドメインを取得
        $SubdomainId = DB::table('subdomains')->where('subdomain', 'www')->value('id');
        $subdomainUserRoleId = DB::table('roles')->where('name', 'subdomain_user')->value('id');
        $subdomainBusinessRoleId = DB::table('roles')->where('name', 'subdomain_business')->value('id');

        if (!$SubdomainId || !$subdomainUserRoleId || !$subdomainBusinessRoleId) {
            $this->command->error('サブドメインまたはロールが見つかりません。先にMain用のSeederを実行してください。');
            return;
        }

        // テスト利用者アカウントを作成
        DB::table('users')->updateOrInsert(
            ['email' => 'test_user@example.com'],
            [
                'subdomain_id' => $SubdomainId,
                'role_id' => $subdomainUserRoleId,
                'login_id' => 'test_user@example.com',
                'name' => 'テスト利用者',
                'display_name' => 'テスト利用者',
                'email' => 'test_user@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // テスト事業者アカウントを作成
        DB::table('users')->updateOrInsert(
            ['email' => 'test_business@example.com'],
            [
                'subdomain_id' => $SubdomainId,
                'role_id' => $subdomainBusinessRoleId,
                'login_id' => 'test_business@example.com',
                'name' => 'テスト事業者',
                'display_name' => 'テスト事業者',
                'email' => 'test_business@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

