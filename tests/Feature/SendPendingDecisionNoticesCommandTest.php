<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\Voucher;
use App\Services\PdfTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
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
            'pending_voucher_issue' => true,
        ]);

        $this->artisan('app:send-pending-decision-notices')
            ->assertSuccessful();

        $beneficiary->refresh();
        $this->assertSame('決定通知書送信失敗', $beneficiary->status);
        $this->assertNotNull($beneficiary->system_message);
        $this->assertStringContainsString('こどもIDが空です', $beneficiary->system_message);
        $this->assertFalse($beneficiary->pending_voucher_issue);
        $this->assertDatabaseCount('vouchers', 0);
    }

    /**
     * pending_voucher_issue=true かつメール成功時はクーポンを付与する
     */
    public function test_command_issues_voucher_when_pending_flag_is_true_and_mail_succeeds(): void
    {
        Mail::fake();
        $this->mockPdfTemplateService();

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'voucher_amount' => 10000,
            'voucher_expiry' => 6,
        ]);

        Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'user_id' => null,
            'child_id' => 'CHILD-SUCCESS-1',
            'guardian_email' => 'guardian@example.com',
            'status' => '決定通知書送信待ち',
            'pending_voucher_issue' => true,
        ]);

        $this->artisan('app:send-pending-decision-notices')
            ->assertSuccessful();

        $beneficiary->refresh();
        $this->assertSame('決定通知書送信済', $beneficiary->status);
        $this->assertFalse($beneficiary->pending_voucher_issue);
        $this->assertNotNull($beneficiary->user_id);
        $this->assertDatabaseHas('vouchers', [
            'beneficiary_id' => $beneficiary->id,
            'subdomain_id' => $subdomain->id,
            'amount' => 10000,
            'status' => 'unused',
        ]);
        $this->assertSame(1, Voucher::where('beneficiary_id', $beneficiary->id)->count());
    }

    /**
     * pending_voucher_issue=false かつメール成功時はクーポンを付与しない
     */
    public function test_command_does_not_issue_voucher_when_pending_flag_is_false(): void
    {
        Mail::fake();
        $this->mockPdfTemplateService();

        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'voucher_amount' => 10000,
            'voucher_expiry' => 6,
        ]);

        Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'user_id' => null,
            'child_id' => 'CHILD-SUCCESS-2',
            'guardian_email' => 'guardian2@example.com',
            'status' => '決定通知書送信待ち',
            'pending_voucher_issue' => false,
        ]);

        $this->artisan('app:send-pending-decision-notices')
            ->assertSuccessful();

        $beneficiary->refresh();
        $this->assertSame('決定通知書送信済', $beneficiary->status);
        $this->assertFalse($beneficiary->pending_voucher_issue);
        $this->assertDatabaseCount('vouchers', 0);
    }

    private function mockPdfTemplateService(): void
    {
        $mock = Mockery::mock(PdfTemplateService::class);
        $mock->shouldReceive('generateNoticePdf')
            ->andReturnUsing(function (): string {
                $path = tempnam(sys_get_temp_dir(), 'notice_').'.pdf';
                file_put_contents($path, '%PDF-1.4 mock');

                return $path;
            });

        $this->app->instance(PdfTemplateService::class, $mock);
    }
}
