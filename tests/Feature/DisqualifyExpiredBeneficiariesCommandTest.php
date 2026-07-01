<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
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
            'is_disqualified' => false,
            'disqualification_date' => $yesterday,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'is_disqualified' => true,
        ]);
    }

    /**
     * 既にis_disqualifiedが1のレコードは更新されないことをテスト
     */
    public function test_does_not_update_already_disqualified_beneficiaries(): void
    {
        $yesterday = Carbon::yesterday();

        $beneficiary = Beneficiary::factory()->create([
            'is_disqualified' => true,
            'disqualification_date' => $yesterday,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'is_disqualified' => true,
        ]);
    }

    /**
     * disqualification_dateが未来のレコードは更新されないことをテスト
     */
    public function test_does_not_update_future_disqualification_date(): void
    {
        $tomorrow = Carbon::tomorrow();

        $beneficiary = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => $tomorrow,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'is_disqualified' => false,
        ]);
    }

    /**
     * 今日の日付のレコードも更新されることをテスト
     */
    public function test_updates_today_disqualification_date(): void
    {
        $today = Carbon::today();

        $beneficiary = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => $today,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'is_disqualified' => true,
        ]);
    }

    /**
     * disqualification_dateがnullのレコードは更新されないことをテスト
     */
    public function test_does_not_update_null_disqualification_date(): void
    {
        $beneficiary = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => null,
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'is_disqualified' => false,
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
            'is_disqualified' => false,
            'disqualification_date' => $twoDaysAgo,
        ]);

        $beneficiary2 = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => $yesterday,
        ]);

        $beneficiary3 = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => $today,
        ]);

        // 更新されないレコード
        $beneficiary4 = Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => Carbon::tomorrow(),
        ]);

        Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary1->id,
            'is_disqualified' => true,
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary2->id,
            'is_disqualified' => true,
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary3->id,
            'is_disqualified' => true,
        ]);

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary4->id,
            'is_disqualified' => false,
        ]);
    }

    /**
     * 対象レコードがない場合のテスト
     */
    public function test_handles_no_target_records(): void
    {
        Beneficiary::factory()->create([
            'is_disqualified' => false,
            'disqualification_date' => Carbon::tomorrow(),
        ]);

        $result = Artisan::call('app:disqualify-expired-beneficiaries');

        $this->assertEquals(0, $result);
    }
}
