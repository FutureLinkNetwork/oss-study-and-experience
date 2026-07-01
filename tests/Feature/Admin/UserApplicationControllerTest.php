<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\UserApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Subdomain $subdomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 50,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin1',
            'is_active' => true,
        ]);
    }

    private function createUserApplication(array $overrides = []): UserApplication
    {
        return UserApplication::create(array_merge([
            'subdomain_id' => $this->subdomain->id,
            'certification_number' => 'TEST-001',
            'guardian_name' => '保護者名',
            'guardian_birth_date' => '1980-01-01',
            'guardian_address' => '住所',
            'guardian_phone' => '090-1234-5678',
            'guardian_email' => 'test@example.com',
            'child_name' => '児童名',
            'child_birth_date' => '2015-04-01',
            'elementary_school_name' => 'テスト小学校',
            'grade' => '3年生',
            'child_address' => '児童住所',
            'survey_consent' => false,
            'is_exported' => false,
        ], $overrides));
    }

    public function test_user_applications_index_displays_correctly(): void
    {
        $this->createUserApplication();

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/user-applications');

        $response->assertStatus(200);
        $response->assertViewIs('admin.user-applications.index');
    }

    public function test_user_application_show_displays_correctly(): void
    {
        $app = $this->createUserApplication();

        $response = $this->actingAs($this->adminUser)
            ->get("http://test.localhost/admin/user-applications/{$app->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.user-applications.show');
        $response->assertSee('ダウンロード対象外');
        $response->assertSee('備考');
    }

    public function test_user_application_update_saves_excluded_and_remarks(): void
    {
        $app = $this->createUserApplication([
            'is_excluded_from_download' => false,
            'admin_remarks' => null,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put("http://test.localhost/admin/user-applications/{$app->id}", [
                '_token' => csrf_token(),
                '_method' => 'PUT',
                'is_excluded_from_download' => '1',
                'admin_remarks' => 'テスト備考',
            ]);

        $response->assertRedirect(route('admin.user-applications.show', $app));
        $response->assertSessionHas('success');

        $app->refresh();
        $this->assertTrue($app->is_excluded_from_download);
        $this->assertSame('テスト備考', $app->admin_remarks);
    }

    public function test_user_application_update_can_clear_excluded(): void
    {
        $app = $this->createUserApplication([
            'is_excluded_from_download' => true,
            'admin_remarks' => 'メモ',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put("http://test.localhost/admin/user-applications/{$app->id}", [
                '_token' => csrf_token(),
                '_method' => 'PUT',
                'admin_remarks' => 'メモ',
            ]);

        $response->assertRedirect(route('admin.user-applications.show', $app));

        $app->refresh();
        $this->assertFalse($app->is_excluded_from_download);
    }

    public function test_index_filter_excluded_shows_only_excluded(): void
    {
        $this->createUserApplication(['is_excluded_from_download' => false, 'child_name' => '対象内']);
        $this->createUserApplication(['is_excluded_from_download' => true, 'child_name' => '対象外児童']);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/user-applications?is_exported=excluded');

        $response->assertStatus(200);
        $response->assertSee('対象外児童');
        $response->assertDontSee('対象内');
    }

    public function test_index_hides_csv_button_when_filter_is_excluded(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/user-applications?is_exported=excluded');

        $response->assertStatus(200);
        $response->assertDontSee('CSV出力');
    }

    public function test_index_shows_csv_button_when_filter_is_not_excluded(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/user-applications');

        $response->assertStatus(200);
        $response->assertSee('CSV出力');
    }

    public function test_export_excludes_excluded_applications(): void
    {
        $included = $this->createUserApplication(['is_excluded_from_download' => false, 'child_name' => '含まれる']);
        $excluded = $this->createUserApplication(['is_excluded_from_download' => true, 'child_name' => '除外される']);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/user-applications/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $body = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('含まれる', $body);
        $this->assertStringNotContainsString('除外される', $body);
    }

    public function test_user_applications_index_requires_level_40_or_above(): void
    {
        $lowLevelRole = Role::factory()->create([
            'name' => 'low',
            'level' => 30,
            'is_active' => true,
        ]);

        $lowUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $lowLevelRole->id,
            'login_id' => 'low1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lowUser)
            ->get('http://test.localhost/admin/user-applications');

        $response->assertStatus(403);
    }
}
