<?php

namespace Tests\Feature;

use App\Console\Commands\CheckCorrectionAndAggregate;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CheckCorrectionAndAggregateCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CACHE_KEY = 'check_correction_last_run_at';

    protected function tearDown(): void
    {
        Cache::forget(self::CACHE_KEY);
        parent::tearDown();
    }

    public function test_first_run_sets_cache_and_does_not_run_batches(): void
    {
        Cache::forget(self::CACHE_KEY);

        $this->artisan('app:check-correction-and-aggregate')
            ->assertSuccessful();

        $this->assertNotNull(Cache::get(self::CACHE_KEY));
    }

    public function test_no_correction_since_last_check_does_not_run_batches(): void
    {
        Cache::put(self::CACHE_KEY, now()->toDateTimeString(), 60);

        $this->artisan('app:check-correction-and-aggregate')
            ->assertSuccessful();

        $this->assertNotNull(Cache::get(self::CACHE_KEY));
    }

    public function test_correction_detected_runs_both_batches(): void
    {
        $lastCheckAt = now()->subHour();
        Cache::put(self::CACHE_KEY, $lastCheckAt->toDateTimeString(), 60);

        $this->createVoucherUsageWithCorrection(now());

        // コマンド内の Artisan::call のみモック（$this->artisan() は使わずコマンドを直接実行）
        Artisan::shouldReceive('call')
            ->with('app:aggregate-business-payments')
            ->once()
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('app:generate-monthly-accounting-reports')
            ->once()
            ->andReturn(0);

        $command = $this->app->make(CheckCorrectionAndAggregate::class);
        $command->setOutput(
            new \Illuminate\Console\OutputStyle(
                new \Symfony\Component\Console\Input\ArrayInput([]),
                new \Symfony\Component\Console\Output\NullOutput
            )
        );
        $exitCode = $command->handle();

        $this->assertSame(0, $exitCode);
        $this->assertNotNull(Cache::get(self::CACHE_KEY));
    }

    public function test_correction_before_last_check_does_not_run_batches(): void
    {
        $lastCheckAt = now()->subMinute();
        Cache::put(self::CACHE_KEY, $lastCheckAt->toDateTimeString(), 60);

        $this->createVoucherUsageWithCorrection(now()->subMinutes(2));

        $this->artisan('app:check-correction-and-aggregate')
            ->assertSuccessful();
    }

    private function createVoucherUsageWithCorrection(Carbon $adminCorrectedAt): VoucherUsage
    {
        $subdomain = Subdomain::factory()->create();
        $businessRole = Role::create([
            'name' => 'subdomain_business',
            'display_name' => '事業者',
            'is_global' => false,
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::create([
            'name' => 'subdomain_user',
            'display_name' => '利用者',
            'is_global' => false,
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'biz_'.uniqid(),
            'email' => 'biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'usr_'.uniqid(),
            'email' => 'usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'Test Business',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '東京都',
            'city' => '渋谷区',
            'address1' => '1-1',
            'phone' => '0300000000',
            'email' => 'b@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'Test Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'Test Course',
            'price' => 5000,
            'is_active' => 1,
        ]);

        return VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 3000,
            'used_at' => Carbon::today()->subMonth()->startOfDay(),
            'admin_corrected_at' => $adminCorrectedAt,
            'is_cancelled' => false,
        ]);
    }
}
