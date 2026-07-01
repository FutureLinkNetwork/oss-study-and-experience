<?php

namespace Database\Seeders\Main;

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
        // サブドメインと管理者ロールを取得
        $subdomainId = DB::table('subdomains')->where('subdomain', 'www')->value('id');
        $subdomainAdminRoleId = DB::table('roles')->where('name', 'subdomain_admin')->value('id');

        if (! $subdomainId || ! $subdomainAdminRoleId) {
            $this->command->error('サブドメインまたはロールが見つかりません。先にSubdomainSeederとRoleSeederを実行してください。');

            return;
        }

        //システム管理者ユーザーを作成
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'subdomain_id' => $subdomainId,
                'role_id' => $subdomainAdminRoleId,
                'login_id' => 'admin',
                'name' => 'システム管理者',
                'display_name' => 'システム管理者',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
