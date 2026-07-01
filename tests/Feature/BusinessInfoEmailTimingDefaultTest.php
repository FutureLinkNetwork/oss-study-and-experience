<?php

namespace Tests\Feature;

use App\Models\Subdomain;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessInfoEmailTimingDefaultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * email_timing を省略して登録すると immediate が自動設定されること
     */
    public function test_email_timing_defaults_to_immediate_when_omitted(): void
    {
        $subdomain = Subdomain::factory()->create();

        DB::table('business_infos')->insert([
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'デフォルト値テスト事業者',
            'business_name_kana' => 'デフォルトチテストジギョウシャ',
            'representative_name' => '代表 太郎',
            'representative_name_kana' => 'ダイヒョウ タロウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'email-timing-default@example.com',
            'apply' => 0,
            'is_active' => 1,
            'status' => '未着手',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('business_infos')
            ->where('email', 'email-timing-default@example.com')
            ->first();

        $this->assertNotNull($created);
        $this->assertSame('immediate', $created->email_timing);
    }

    /**
     * email_timing に null を明示的に設定して登録できないこと
     */
    public function test_email_timing_cannot_be_explicitly_set_to_null(): void
    {
        $subdomain = Subdomain::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('business_infos')->insert([
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'NULLテスト事業者',
            'business_name_kana' => 'ヌルテストジギョウシャ',
            'representative_name' => '代表 花子',
            'representative_name_kana' => 'ダイヒョウ ハナコ',
            'postal_code' => '664-0002',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '中央1-2-3',
            'phone' => '072-765-4321',
            'email' => 'email-timing-null@example.com',
            'email_timing' => null,
            'apply' => 0,
            'is_active' => 1,
            'status' => '未着手',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
