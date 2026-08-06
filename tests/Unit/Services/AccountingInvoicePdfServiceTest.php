<?php

namespace Tests\Unit\Services;

use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Services\AccountingInvoicePdfService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AccountingInvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mayor_recipient_label_uses_subdomain_name(): void
    {
        $subdomain = Subdomain::factory()->create([
            'name' => 'テスト市',
            'system_name' => 'テスト市子どもの習い事応援事業請求書',
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $method = new ReflectionMethod(AccountingInvoicePdfService::class, 'mayorRecipientLabel');

        $this->assertSame('テスト市長 様', $method->invoke($service, $subdomain));
    }

    public function test_invoice_title_uses_subdomain_system_name(): void
    {
        $subdomain = Subdomain::factory()->create([
            'name' => 'テスト市',
            'system_name' => 'テスト市子どもの習い事応援事業請求書',
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $method = new ReflectionMethod(AccountingInvoicePdfService::class, 'invoiceTitle');

        $this->assertSame('テスト市子どもの習い事応援事業請求書', $method->invoke($service, $subdomain));
    }

    public function test_invoice_title_falls_back_to_empty_string_when_system_name_is_null(): void
    {
        $subdomain = Subdomain::factory()->create([
            'name' => 'テスト市',
            'system_name' => null,
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $method = new ReflectionMethod(AccountingInvoicePdfService::class, 'invoiceTitle');

        $this->assertSame('', $method->invoke($service, $subdomain));
    }

    public function test_invoice_preamble_uses_subdomain_invoice_preamble_field(): void
    {
        $subdomain = Subdomain::factory()->create([
            'invoice_preamble' => '○○市条例第1条の規定に基づき、下記のとおり請求します。',
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $method = new ReflectionMethod(AccountingInvoicePdfService::class, 'invoicePreamble');

        $this->assertSame(
            '○○市条例第1条の規定に基づき、下記のとおり請求します。',
            $method->invoke($service, $subdomain)
        );
    }

    public function test_invoice_preamble_falls_back_to_empty_string_when_null(): void
    {
        $subdomain = Subdomain::factory()->create([
            'invoice_preamble' => null,
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $method = new ReflectionMethod(AccountingInvoicePdfService::class, 'invoicePreamble');

        $this->assertSame('', $method->invoke($service, $subdomain));
    }

    public function test_generate_for_subdomain_creates_pdf_with_custom_subdomain_labels(): void
    {
        $lastMonth = Carbon::today()->subMonth();
        $targetYearMonth = $lastMonth->format('Y-m');

        $subdomain = Subdomain::factory()->create([
            'name' => '尼崎市',
            'system_name' => '尼崎市子どもの習い事応援事業請求書',
        ]);

        $businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);
        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $businessUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $businessRole->id,
            'login_id' => 'acct_pdf_biz_'.uniqid(),
            'email' => 'acct_pdf_biz_'.uniqid().'@example.com',
        ]);
        $consumerUser = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'acct_pdf_usr_'.uniqid(),
            'email' => 'acct_pdf_usr_'.uniqid().'@example.com',
        ]);

        $business = BusinessInfo::create([
            'user_id' => $businessUser->id,
            'subdomain_id' => $subdomain->id,
            'applicant_type' => 'individual',
            'business_name' => 'PDF Label Biz',
            'business_name_kana' => 'テスト',
            'representative_name' => 'Rep',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '1234567',
            'prefecture' => '兵庫県',
            'city' => '尼崎市',
            'address1' => '1-1',
            'phone' => '0600000000',
            'email' => 'acct-pdf@example.com',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $classroom = ClassroomInfo::create([
            'business_info_id' => $business->id,
            'classroom_name' => 'PDF Classroom',
            'classroom_name_kana' => 'テスト',
            'apply' => 1,
            'is_active' => 1,
        ]);
        $course = CourseInfo::create([
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_name' => 'PDF Course',
            'price' => 3000,
            'is_active' => 1,
        ]);

        VoucherUsage::create([
            'user_id' => $consumerUser->id,
            'subdomain_id' => $subdomain->id,
            'business_info_id' => $business->id,
            'classroom_info_id' => $classroom->id,
            'course_info_id' => $course->id,
            'amount' => 3000,
            'used_at' => $lastMonth->copy()->day(10)->startOfDay(),
            'is_cancelled' => false,
        ]);

        $service = app(AccountingInvoicePdfService::class);
        $pdfPath = $service->generateForSubdomain($subdomain, $targetYearMonth);

        $this->assertFileExists($pdfPath);
        $content = (string) file_get_contents($pdfPath);
        $this->assertStringStartsWith('%PDF', $content);
        $this->assertGreaterThan(2000, strlen($content));

        @unlink($pdfPath);
    }
}
