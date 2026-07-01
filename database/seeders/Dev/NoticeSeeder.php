<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoticeSeeder extends Seeder
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

        // サブドメインと管理者ユーザーを取得
        $SubdomainId = DB::table('subdomains')->where('subdomain', 'www')->value('id');
        $adminUserId = DB::table('users')
            ->where('subdomain_id', $SubdomainId)
            ->where('login_id', 'admin')
            ->value('id');

        if (!$SubdomainId || !$adminUserId) {
            $this->command->error('サブドメインまたは管理者ユーザーが見つかりません。先にMain用のSeederを実行してください。');
            return;
        }

        // サンプルお知らせデータを作成
        $existing = DB::table('notices')
            ->where('subdomain_id', $SubdomainId)
            ->where('title', '習い事クーポン制度開始のお知らせ')
            ->first();

        if ($existing) {
            return;
        }

        DB::table('notices')->insert([
            'subdomain_id' => $SubdomainId,
            'title' => '習い事クーポン制度開始のお知らせ',
            'content' => "子どもたちの習い事活動を支援するため、習い事クーポン制度を開始いたします。\n\n【対象者】\n・市内在住の小学生・中学生\n・世帯年収が一定基準以下の家庭\n\n【支援内容】\n・月額最大10,000円のクーポン支給\n・年間最大5つの習い事が対象\n\n【申請期間】\n2025年4月1日〜2026年3月31日\n\n詳細については、市役所こども政策課までお問い合わせください。",
            'notice_date' => now()->toDateString(),
            'publish_start_at' => now(),
            'publish_end_at' => now()->addMonths(12),
            'address' => '',
            'latitude' => 34.647874,
            'longitude' => 135.0000,
            'link_url' => '',
            'show_on_public' => true,
            'show_on_user_dashboard' => true,
            'show_on_business_dashboard' => true,
            'is_deleted' => false,
            'created_user' => $adminUserId,
            'updated_user' => $adminUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

