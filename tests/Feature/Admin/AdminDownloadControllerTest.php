<?php

namespace Tests\Feature\Admin;

use App\Models\AdminDownload;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Subdomain $subdomain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subdomain = Subdomain::factory()->create(['subdomain' => 'test', 'is_active' => true]);
        $role = Role::factory()->create(['name' => 'subdomain_admin', 'level' => 50, 'is_active' => true]);
        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $role->id,
            'login_id' => 'downloadadmin',
            'is_active' => true,
        ]);
    }

    public function test_index_displays_downloads_and_can_filter_by_download_type(): void
    {
        AdminDownload::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'summary' => '2025年4月1日 0時時点 利用者CSV 全件',
            'download_type' => 'beneficiary',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/downloads');

        $response->assertStatus(200);
        $response->assertViewIs('admin.downloads.index');
        $response->assertSee('利用者CSV');

        $response2 = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/downloads?download_type=beneficiary');

        $response2->assertStatus(200);
        $response2->assertViewIs('admin.downloads.index');
        $response2->assertSee('利用者CSV');
    }

    public function test_download_returns_csv_from_s3(): void
    {
        Storage::fake('s3');
        $s3Key = "subdomain_{$this->subdomain->id}/beneficiary_exports/2025-04-01.csv";
        Storage::disk('s3')->put($s3Key, 'dummy,csv,content');

        $record = AdminDownload::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'exported_at' => now()->setDate(2025, 4, 1)->startOfDay(),
            'summary' => 'test',
            's3_key' => $s3Key,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://test.localhost/admin/downloads/'.$record->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=Shift_JIS');
        $this->assertSame('dummy,csv,content', $response->streamedContent());
    }

    public function test_index_requires_level_40_or_above(): void
    {
        $lowRole = Role::factory()->create(['name' => 'subdomain_viewer', 'level' => 30, 'is_active' => true]);
        $lowUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $lowRole->id,
            'login_id' => 'lowuser',
            'is_active' => true,
        ]);

        $response = $this->actingAs($lowUser)
            ->get('http://test.localhost/admin/downloads');

        $response->assertStatus(403);
    }
}
