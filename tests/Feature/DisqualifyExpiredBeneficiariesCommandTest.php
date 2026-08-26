<?php

namespace Tests\Feature;

use App\Mail\DisqualificationNoticeMail;
use App\Models\Beneficiary;
use App\Models\Subdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DisqualifyExpiredBeneficiariesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 対象レコードが正常に更新されることをテスト
     */
    public function test_updates_expired_beneficiaries(): void
    {
        $yesterday = Carbon::yesterday();

        $beneficiary = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => $yesterday,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '資格喪失',
        ]);
    }

    /**
     * 既に資格喪失のレコードは更新されないことをテスト
     */
    public function test_does_not_update_already_disqualified_beneficiaries(): void
    {
        $yesterday = Carbon::yesterday();

        $beneficiary = Beneficiary::factory()->create([
            'status' => '資格喪失',
            'disqualification_date' => $yesterday,
        ]);

        $updatedAt = $beneficiary->updated_at;

        Artisan::call('app:disqualify-expired-beneficiaries');

        $beneficiary->refresh();
        $this->assertSame('資格喪失', $beneficiary->status);
        $this->assertTrue($beneficiary->updated_at->equalTo($updatedAt));
    }

    /**
     * disqualification_dateが未来のレコードは更新されないことをテスト
     */
    public function test_does_not_update_future_disqualification_date(): void
    {
        $tomorrow = Carbon::tomorrow();

        $beneficiary = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => $tomorrow,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => 'ログイン認証済み',
        ]);
    }

    /**
     * 今日の日付のレコードも更新されることをテスト
     */
    public function test_updates_today_disqualification_date(): void
    {
        $today = Carbon::today();

        $beneficiary = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => $today,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '資格喪失',
        ]);
    }

    /**
     * disqualification_dateがnullのレコードは更新されないことをテスト
     */
    public function test_does_not_update_null_disqualification_date(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => null,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => 'ログイン認証済み',
        ]);
    }

    /**
     * 複数の対象レコードが正常に更新されることをテスト
     */
    public function test_updates_multiple_expired_beneficiaries(): void
    {
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();
        $twoDaysAgo = Carbon::today()->subDays(2);

        $beneficiary1 = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => $twoDaysAgo,
        ]);

        $beneficiary2 = Beneficiary::factory()->create([
            'status' => '資格喪失予定',
            'disqualification_date' => $yesterday,
        ]);

        $beneficiary3 = Beneficiary::factory()->create([
            'status' => '決定通知書送信済',
            'disqualification_date' => $today,
        ]);

        $beneficiary4 = Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => Carbon::tomorrow(),
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary1->id,
            'status' => '資格喪失',
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary2->id,
            'status' => '資格喪失',
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary3->id,
            'status' => '資格喪失',
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary4->id,
            'status' => 'ログイン認証済み',
        ]);
    }

    /**
     * 対象レコードがない場合のテスト
     */
    public function test_handles_no_target_records(): void
    {
        Beneficiary::factory()->create([
            'status' => 'ログイン認証済み',
            'disqualification_date' => Carbon::tomorrow(),
        ]);

        $result = Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertEquals(0, $result);
    }

    /**
     * 資格喪失に更新した利用者へ指定の通知メールを送信する
     */
    public function test_sends_disqualification_notice_mail_when_status_is_updated(): void
    {
        Mail::fake();

        $systemName = '伊丹市子どもの習い事応援事業';
        $subdomain = Subdomain::factory()->create([
            'subdomain' => 'itami',
            'system_name' => $systemName,
        ]);

        $beneficiary = Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'status' => '資格喪失予定',
            'disqualification_date' => Carbon::today(),
            'guardian_email' => 'guardian@example.com',
        ]);

        $this->artisan('app:disqualify-expired-beneficiaries')
            ->assertSuccessful();

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '資格喪失',
        ]);

        Mail::assertSent(DisqualificationNoticeMail::class, function (DisqualificationNoticeMail $mail) use ($systemName): bool {
            $this->assertTrue($mail->hasTo('guardian@example.com'));
            $this->assertSame($systemName, $mail->systemName);
            $this->assertSame(
                '伊丹市子どもの習い事応援事業にかかる資格要件の審査結果について',
                $mail->envelope()->subject
            );

            $body = $mail->render();
            $this->assertStringContainsString('いつもお世話になります。伊丹市子どもの習い事応援事業事務局です。', $body);
            $this->assertStringContainsString('クーポンの付与はございません。', $body);
            $this->assertStringContainsString('既に付与済のクーポンについては、今年度末までご利用いただけます。', $body);
            $this->assertStringContainsString('利用者マイページ内の「お問い合わせ」', $body);

            return true;
        });
        Mail::assertSentCount(1);
    }

    /**
     * サブドメインのシステム名が空の場合は伊丹市の事業名で送信する
     */
    public function test_uses_fallback_system_name_when_subdomain_system_name_is_empty(): void
    {
        Mail::fake();

        $subdomain = Subdomain::factory()->create([
            'system_name' => '',
        ]);

        Beneficiary::factory()->create([
            'subdomain_id' => $subdomain->id,
            'status' => '資格喪失予定',
            'disqualification_date' => Carbon::today(),
            'guardian_email' => 'guardian@example.com',
        ]);

        $this->artisan('app:disqualify-expired-beneficiaries')
            ->assertSuccessful();

        Mail::assertSent(DisqualificationNoticeMail::class, function (DisqualificationNoticeMail $mail): bool {
            $this->assertSame('伊丹市子どもの習い事応援事業', $mail->systemName);
            $this->assertSame(
                '伊丹市子どもの習い事応援事業にかかる資格要件の審査結果について',
                $mail->envelope()->subject
            );

            return true;
        });
    }

    /**
     * 既に資格喪失の利用者にはメールを送信しない
     */
    public function test_does_not_send_mail_to_already_disqualified_beneficiaries(): void
    {
        Mail::fake();

        Beneficiary::factory()->create([
            'status' => '資格喪失',
            'disqualification_date' => Carbon::yesterday(),
            'guardian_email' => 'already@example.com',
        ]);

        $this->artisan('app:disqualify-expired-beneficiaries')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    /**
     * メールアドレスが不正でもステータスは資格喪失に更新する
     */
    public function test_updates_status_even_when_email_is_invalid(): void
    {
        Mail::fake();

        $beneficiary = Beneficiary::factory()->create([
            'status' => '資格喪失予定',
            'disqualification_date' => Carbon::today(),
            'guardian_email' => 'invalid-email',
        ]);

        $this->artisan('app:disqualify-expired-beneficiaries')
            ->assertSuccessful();

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '資格喪失',
        ]);
        Mail::assertNothingSent();
    }

    /**
     * メール送信に失敗してもステータス更新は成功する
     */
    public function test_updates_status_even_when_mail_sending_fails(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP error'));

        $beneficiary = Beneficiary::factory()->create([
            'status' => '資格喪失予定',
            'disqualification_date' => Carbon::today(),
            'guardian_email' => 'guardian@example.com',
        ]);

        $this->artisan('app:disqualify-expired-beneficiaries')
            ->assertSuccessful();

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => '資格喪失',
        ]);
    }
}
