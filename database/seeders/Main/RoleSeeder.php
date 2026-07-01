<?php

namespace Database\Seeders\Main;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'システム管理者',
                'description' => '全システム管理者',
                'is_global' => true,
                'level' => 100,
                'permissions' => [
                    'system' => ['create', 'read', 'update', 'delete'],
                    'subdomain' => ['create', 'read', 'update', 'delete'],
                    'user' => ['create', 'read', 'update', 'delete'],
                    'business' => ['create', 'read', 'update', 'delete'],
                    'notice' => ['create', 'read', 'update', 'delete'],
                    'category' => ['create', 'read', 'update', 'delete'],
                ],
            ],
            [
                'name' => 'subdomain_admin',
                'display_name' => 'サブドメイン管理者',
                'description' => 'サブドメイン内全管理',
                'is_global' => false,
                'level' => 80,
                'permissions' => [
                    'user' => ['create', 'read', 'update', 'delete'],
                    'business' => ['create', 'read', 'update', 'delete'],
                    'voucher' => ['create', 'read', 'update', 'delete'],
                    'notice' => ['create', 'read', 'update', 'delete'],
                    'category' => ['create', 'read', 'update', 'delete'],
                ],
            ],
            [
                'name' => 'subdomain_operator',
                'display_name' => 'サブドメイン作業者',
                'description' => '業務操作・限定的管理',
                'is_global' => false,
                'level' => 60,
                'permissions' => [
                    'user' => ['read', 'update'],
                    'business' => ['read', 'update'],
                    'voucher' => ['create', 'read', 'update'],
                    'notice' => ['create', 'read', 'update'],
                ],
            ],
            [
                'name' => 'subdomain_viewer',
                'display_name' => 'サブドメイン閲覧者',
                'description' => '参照のみ・レポート閲覧',
                'is_global' => false,
                'level' => 40,
                'permissions' => [
                    'user' => ['read'],
                    'business' => ['read'],
                    'voucher' => ['read'],
                    'notice' => ['read'],
                ],
            ],
            [
                'name' => 'subdomain_business',
                'display_name' => 'サブドメイン事業者',
                'description' => '事業者向け機能',
                'is_global' => false,
                'level' => 20,
                'permissions' => [
                    'business' => ['read', 'update'],
                    'classroom' => ['create', 'read', 'update'],
                    'course' => ['create', 'read', 'update'],
                ],
            ],
            [
                'name' => 'subdomain_user',
                'display_name' => 'サブドメイン利用者',
                'description' => '利用者向け機能',
                'is_global' => false,
                'level' => 10,
                'permissions' => [
                    'profile' => ['read', 'update'],
                    'voucher' => ['create', 'read'],
                ],
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                array_merge($role, [
                    'permissions' => json_encode($role['permissions']),
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
