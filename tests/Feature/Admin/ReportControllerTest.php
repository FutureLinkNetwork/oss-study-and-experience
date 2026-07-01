<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(Subdomain $subdomain, int $level = 80): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'subdomain_admin'],
            [
                'display_name' => '管理者',
                'description' => null,
                'is_global' => false,
                'level' => $level,
                'permissions' => null,
                'is_active' => 1,
            ]
        );
        if ($role->level !== $level) {
            $role->update(['level' => $level]);
        }

        return User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'admin_report_'.uniqid(),
            'email' => 'admin_report_'.uniqid().'@example.com',
        ]);
    }

    public function test_report_index_requires_auth(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $response = $this->get('http://www.localhost/admin/reports');

        $response->assertRedirect();
    }

    public function test_report_index_requires_level_40(): void
    {
        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::firstOrCreate(
            ['name' => 'subdomain_viewer'],
            [
                'display_name' => '閲覧者',
                'description' => null,
                'is_global' => false,
                'level' => 30,
                'permissions' => null,
                'is_active' => 1,
            ]
        );
        $user = User::factory()->create([
            'subdomain_id' => $subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'viewer_'.uniqid(),
            'email' => 'viewer_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/reports');

        $response->assertStatus(403);
    }

    public function test_report_index_returns_200_and_displays_last_12_months_and_descriptions(): void
    {
        $this->withoutVite();

        $subdomain = Subdomain::factory()->create(['subdomain' => 'www']);
        $admin = $this->createAdminUser($subdomain, 40);

        $response = $this->actingAs($admin)->get('http://www.localhost/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('レポート（過去12ヶ月）');
        $response->assertSee('クーポン・利用状況');
        $response->assertSee('事業者・教室・コース数');
        $response->assertSee('月次人気教室トップ20');
        $response->assertSee('申請・審査の推移');
        $response->assertSee('登録事業者の種別分布');
        $response->assertSee('クーポン利用の習い事の種別分布');
        $response->assertSee('集計基準');
        $response->assertSee('月次クーポン発行利用者数');
        $response->assertSee('月次事業者数');
        $response->assertSee('adminReportChartData');
        $response->assertSee('adminReportCouponChart');
        $response->assertSee('adminReportEntityChart');
        $response->assertSee('adminReportBumpChart');
        $response->assertSee('adminReportApplicantTypeChart');
        $response->assertSee('adminReportUsageByLessonCategoryChart');
        $response->assertSee('adminReportApplicationApprovalChart');

        $viewData = $response->viewData('chartData');
        $this->assertIsArray($viewData);
        $this->assertArrayHasKey('monthLabels', $viewData);
        $this->assertCount(12, $viewData['monthLabels']);
        $this->assertArrayHasKey('issuedUserCounts', $viewData);
        $this->assertArrayHasKey('businessCounts', $viewData);
        $this->assertArrayHasKey('bumpChart', $viewData);
        $this->assertIsArray($viewData['bumpChart']);
        $this->assertArrayHasKey('monthLabels', $viewData['bumpChart']);
        $this->assertArrayHasKey('classrooms', $viewData['bumpChart']);
        $this->assertArrayHasKey('applicantTypeChart', $viewData);
        $this->assertIsArray($viewData['applicantTypeChart']);
        $this->assertArrayHasKey('monthLabels', $viewData['applicantTypeChart']);
        $this->assertArrayHasKey('corporation', $viewData['applicantTypeChart']);
        $this->assertArrayHasKey('voluntary_group', $viewData['applicantTypeChart']);
        $this->assertArrayHasKey('individual', $viewData['applicantTypeChart']);
        $this->assertArrayHasKey('government_agency', $viewData['applicantTypeChart']);
        $this->assertArrayHasKey('usageByLessonCategoryChart', $viewData);
        $this->assertIsArray($viewData['usageByLessonCategoryChart']);
        $this->assertArrayHasKey('monthLabels', $viewData['usageByLessonCategoryChart']);
        $this->assertArrayHasKey('labels', $viewData['usageByLessonCategoryChart']);
        $this->assertArrayHasKey('series', $viewData['usageByLessonCategoryChart']);
        $this->assertCount(12, $viewData['usageByLessonCategoryChart']['monthLabels']);
        $this->assertCount(
            count($viewData['usageByLessonCategoryChart']['labels']),
            $viewData['usageByLessonCategoryChart']['series']
        );
        $this->assertArrayHasKey('applicationApprovalChart', $viewData);
        $this->assertIsArray($viewData['applicationApprovalChart']);
        $this->assertArrayHasKey('monthLabels', $viewData['applicationApprovalChart']);
        $this->assertArrayHasKey('userApplicationCounts', $viewData['applicationApprovalChart']);
        $this->assertArrayHasKey('beneficiaryApprovalCounts', $viewData['applicationApprovalChart']);
        $this->assertArrayHasKey('businessApplicationCounts', $viewData['applicationApprovalChart']);
        $this->assertArrayHasKey('businessApprovalCounts', $viewData['applicationApprovalChart']);
        $this->assertCount(12, $viewData['applicationApprovalChart']['monthLabels']);
        $this->assertCount(12, $viewData['applicationApprovalChart']['userApplicationCounts']);
        $this->assertCount(12, $viewData['applicationApprovalChart']['beneficiaryApprovalCounts']);
        $this->assertCount(12, $viewData['applicationApprovalChart']['businessApplicationCounts']);
        $this->assertCount(12, $viewData['applicationApprovalChart']['businessApprovalCounts']);
    }

    public function test_report_index_returns_404_when_user_has_no_subdomain(): void
    {
        Subdomain::factory()->create(['subdomain' => 'www']);
        $role = Role::firstOrCreate(
            ['name' => 'subdomain_admin'],
            [
                'display_name' => '管理者',
                'description' => null,
                'is_global' => false,
                'level' => 80,
                'permissions' => null,
                'is_active' => 1,
            ]
        );
        $user = User::factory()->create([
            'subdomain_id' => null,
            'role_id' => $role->id,
            'login_id' => 'no_sub_'.uniqid(),
            'email' => 'no_sub_'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($user)->get('http://www.localhost/admin/reports');

        $response->assertStatus(404);
    }
}
