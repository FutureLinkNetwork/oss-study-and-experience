<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendPendingDecisionNoticesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 送信待ちが0件の場合は正常終了する
     */
    public function test_command_succeeds_when_no_pending_beneficiaries(): void
    {
        $subdomain = Subdomain::factory()->create();

        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'status' => '決定通知書送信済',
        ]);

        $this->artisan('app:send-pending-decision-notices')
            ->assertSuccessful();
    }

    /**
     * 送信待ちの利用者がいるとバッチが処理を実行する（失敗時は決定通知書送信失敗とsystem_messageを記録）
     */
    public function test_command_records_failure_when_send_fails(): void
    {
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'user_id' => null,
            'child_id' => null,
            'status' => '決定通知書送信待ち',
        ]);

        $this->artisan('app:send-pending-decision-notices')
            ->assertSuccessful();

        $beneficiary->refresh();
        $this->assertSame('決定通知書送信失敗', $beneficiary->status);
        $this->assertNotNull($beneficiary->system_message);
        $this->assertStringContainsString('こどもIDが空です', $beneficiary->system_message);
    }
}
