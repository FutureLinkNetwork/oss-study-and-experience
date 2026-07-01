<?php

namespace Tests\Feature\Admin;

use App\Models\Notice;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoticeAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private Role $adminRole;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        $this->adminRole = Role::create([
            'name' => 'subdomain_admin',
            'display_name' => 'サブドメイン管理者',
            'level' => 50,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->adminRole->id,
            'login_id' => 'admin_test',
            'is_active' => true,
        ]);
    }

    public function test_create_notice_with_attachment_stores_file_and_saves_metadata(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'document.pdf',
            '%PDF-1.4 dummy content'
        );

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.notices.store'), [
                'subdomain_id' => $this->subdomain->id,
                'title' => 'テストお知らせ',
                'content' => '本文',
                'notice_date' => now()->format('Y-m-d'),
                'link_url' => null,
                'show_on_public' => false,
                'show_on_user_dashboard' => false,
                'show_on_business_dashboard' => false,
                'attachment' => $file,
            ]);

        $response->assertRedirect(route('admin.notices.index'));

        $notice = Notice::query()->where('title', 'テストお知らせ')->first();
        $this->assertNotNull($notice);
        $this->assertNotNull($notice->attachment_s3_key);
        $this->assertSame('document.pdf', $notice->attachment_original_filename);
        $this->assertTrue(Storage::disk('s3')->exists($notice->attachment_s3_key));
    }

    public function test_create_notice_without_attachment_succeeds(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.notices.store'), [
                'subdomain_id' => $this->subdomain->id,
                'title' => '添付なしお知らせ',
                'content' => '本文',
                'notice_date' => now()->format('Y-m-d'),
                'link_url' => null,
                'show_on_public' => false,
                'show_on_user_dashboard' => false,
                'show_on_business_dashboard' => false,
            ]);

        $response->assertRedirect(route('admin.notices.index'));

        $notice = Notice::query()->where('title', '添付なしお知らせ')->first();
        $this->assertNotNull($notice);
        $this->assertNull($notice->attachment_s3_key);
    }

    public function test_update_notice_replacing_attachment_deletes_old_from_s3(): void
    {
        $notice = Notice::create([
            'subdomain_id' => $this->subdomain->id,
            'title' => '差し替え対象',
            'content' => '本文',
            'notice_date' => now(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $this->adminUser->id,
            'updated_user' => $this->adminUser->id,
            'attachment_s3_key' => 'subdomain_1/notice_attachments/1/old.pdf',
            'attachment_original_filename' => 'old.pdf',
            'attachment_file_size' => 3,
            'attachment_mime_type' => 'application/pdf',
        ]);

        Storage::disk('s3')->put($notice->attachment_s3_key, 'old');

        $newFile = UploadedFile::fake()->createWithContent(
            'new.pdf',
            '%PDF-1.4 new content'
        );

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.notices.update', $notice), [
                'subdomain_id' => $this->subdomain->id,
                'title' => $notice->title,
                'content' => $notice->content,
                'notice_date' => $notice->notice_date->format('Y-m-d'),
                'link_url' => null,
                'show_on_public' => false,
                'show_on_user_dashboard' => false,
                'show_on_business_dashboard' => false,
                'attachment' => $newFile,
            ]);

        $response->assertRedirect(route('admin.notices.index'));

        $this->assertFalse(Storage::disk('s3')->exists('subdomain_1/notice_attachments/1/old.pdf'));

        $notice->refresh();
        $this->assertNotNull($notice->attachment_s3_key);
        $this->assertSame('new.pdf', $notice->attachment_original_filename);
        $this->assertTrue(Storage::disk('s3')->exists($notice->attachment_s3_key));
    }

    public function test_update_notice_with_attachment_remove_clears_attachment(): void
    {
        $notice = Notice::create([
            'subdomain_id' => $this->subdomain->id,
            'title' => '編集対象',
            'content' => '本文',
            'notice_date' => now(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $this->adminUser->id,
            'updated_user' => $this->adminUser->id,
            'attachment_s3_key' => 'subdomain_1/notice_attachments/1/test.pdf',
            'attachment_original_filename' => 'test.pdf',
            'attachment_file_size' => 100,
            'attachment_mime_type' => 'application/pdf',
        ]);

        Storage::disk('s3')->put($notice->attachment_s3_key, 'dummy');

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.notices.update', $notice), [
                'subdomain_id' => $this->subdomain->id,
                'title' => $notice->title,
                'content' => $notice->content,
                'notice_date' => $notice->notice_date->format('Y-m-d'),
                'link_url' => null,
                'show_on_public' => false,
                'show_on_user_dashboard' => false,
                'show_on_business_dashboard' => false,
                'attachment_remove' => '1',
            ]);

        $response->assertRedirect(route('admin.notices.index'));

        $notice->refresh();
        $this->assertNull($notice->attachment_s3_key);
        $this->assertNull($notice->attachment_original_filename);
        $this->assertFalse(Storage::disk('s3')->exists('subdomain_1/notice_attachments/1/test.pdf'));
    }

    public function test_admin_download_attachment_returns_200_when_authorized(): void
    {
        $notice = Notice::create([
            'subdomain_id' => $this->subdomain->id,
            'title' => '添付あり',
            'content' => '本文',
            'notice_date' => now(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $this->adminUser->id,
            'updated_user' => $this->adminUser->id,
            'attachment_s3_key' => 'subdomain_1/notice_attachments/1/file.pdf',
            'attachment_original_filename' => 'file.pdf',
            'attachment_file_size' => 6,
            'attachment_mime_type' => 'application/pdf',
        ]);

        Storage::disk('s3')->put($notice->attachment_s3_key, 'content');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.notices.attachment.download', $notice));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('file.pdf', $response->headers->get('content-disposition'));
    }

    public function test_admin_download_attachment_returns_404_when_no_attachment(): void
    {
        $notice = Notice::create([
            'subdomain_id' => $this->subdomain->id,
            'title' => '添付なし',
            'content' => '本文',
            'notice_date' => now(),
            'show_on_public' => false,
            'show_on_user_dashboard' => false,
            'show_on_business_dashboard' => false,
            'is_deleted' => false,
            'created_user' => $this->adminUser->id,
            'updated_user' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.notices.attachment.download', $notice));

        $response->assertStatus(404);
    }
}
