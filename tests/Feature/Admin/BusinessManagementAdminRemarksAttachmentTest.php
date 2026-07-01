<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessManagementAdminRemarksAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private User $adminUser;

    private BusinessInfo $business;

    private function validBusinessPayload(): array
    {
        return [
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_title' => '代表取締役',
            'representative_family_name' => 'テスト',
            'representative_given_name' => '代表',
            'representative_title_kana' => 'ダイヒョウトリシマリヤク',
            'representative_family_name_kana' => 'テスト',
            'representative_given_name_kana' => 'ダイヒョウ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'building_name' => '',
            'phone' => '072-123-4567',
            'fax' => '',
            'email' => 'test-remarks@example.com',
            'website_url' => '',
            'email_timing' => 'immediate',
            'contact_person' => '担当者',
            'contact_phone' => '072-123-4568',
            'document_person' => '宛名',
            'document_address' => '兵庫県伊丹市荻野1-1-1',
            'business_hours' => '9:00-18:00',
            'holiday' => '日曜',
            'bank_code' => '0001',
            'branch_code' => '001',
            'account_type' => '普通',
            'account_number' => '1234567',
            'account_holder_name' => 'テストジギョウシャ',
            'status' => '利用中',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'test',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_remarks_test',
            'is_active' => true,
        ]);

        $this->business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => 'テスト事業者',
            'business_name_kana' => 'テストジギョウシャ',
            'representative_name' => 'テスト代表',
            'representative_name_kana' => 'テストダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野1-1-1',
            'phone' => '072-123-4567',
            'email' => 'test-remarks@example.com',
            'apply' => 1,
            'is_active' => 1,
            'status' => '利用中',
            'qr_only' => false,
        ]);
    }

    public function test_new_business_defaults_is_public_funds_transfer_target_to_false(): void
    {
        $this->assertFalse($this->business->is_public_funds_transfer_target);
    }

    public function test_edit_page_shows_admin_remarks_and_attachment_section(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.edit', $this->business));

        $response->assertStatus(200);
        $response->assertSee('管理者用備考');
        $response->assertSee('新規添付');
        $response->assertSee('admin_remarks');
        $response->assertSee('公金振替対象');
        $response->assertSee('is_public_funds_transfer_target');
    }

    public function test_update_saves_is_public_funds_transfer_target_when_checked(): void
    {
        $this->business->update(['is_public_funds_transfer_target' => false]);

        $payload = $this->validBusinessPayload();
        $payload['is_public_funds_transfer_target'] = '1';

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertTrue($this->business->is_public_funds_transfer_target);
    }

    public function test_update_saves_is_public_funds_transfer_target_false_when_unchecked(): void
    {
        $payload = $this->validBusinessPayload();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertFalse($this->business->is_public_funds_transfer_target);
    }

    public function test_update_saves_admin_remarks(): void
    {
        $payload = $this->validBusinessPayload();
        $payload['admin_remarks'] = '管理者用の備考テキストです。';

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertSame('管理者用の備考テキストです。', $this->business->admin_remarks);
    }

    public function test_update_with_attachment_uploads_to_s3_and_saves_metadata(): void
    {
        $file = UploadedFile::fake()->createWithContent('admin-doc.pdf', 'dummy pdf content');

        $payload = $this->validBusinessPayload();
        $payload['admin_attachments'] = [$file];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertIsArray($this->business->admin_attachments);
        $this->assertCount(1, $this->business->admin_attachments);

        $att = $this->business->admin_attachments[0];
        $this->assertArrayHasKey('s3_key', $att);
        $this->assertArrayHasKey('original_filename', $att);
        $this->assertSame('admin-doc.pdf', $att['original_filename']);
        $this->assertTrue(Storage::disk('s3')->exists($att['s3_key']));
    }

    public function test_download_admin_attachment_returns_file(): void
    {
        $s3Key = 'subdomain_'.$this->subdomain->id.'/business_admin_attachments/'.$this->business->id.'/test-uuid_file.pdf';
        Storage::disk('s3')->put($s3Key, 'dummy content');

        $this->business->update([
            'admin_attachments' => [
                [
                    's3_key' => $s3Key,
                    'size' => 13,
                    'original_filename' => 'file.pdf',
                    'mime_type' => 'application/pdf',
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.business.admin-attachment.download', $this->business).'?key='.urlencode($s3Key));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('file.pdf', $response->headers->get('content-disposition'));
    }

    public function test_update_with_remove_attachment_deletes_from_s3_and_removes_from_json(): void
    {
        $s3Key = 'subdomain_'.$this->subdomain->id.'/business_admin_attachments/'.$this->business->id.'/remove-me.pdf';
        Storage::disk('s3')->put($s3Key, 'dummy');

        $this->business->update([
            'admin_attachments' => [
                [
                    's3_key' => $s3Key,
                    'size' => 5,
                    'original_filename' => 'remove-me.pdf',
                    'mime_type' => 'application/pdf',
                ],
            ],
        ]);

        $payload = $this->validBusinessPayload();
        $payload['admin_attachment_remove'] = [$s3Key];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $this->business->refresh();
        $this->assertSame([], $this->business->admin_attachments);
        $this->assertFalse(Storage::disk('s3')->exists($s3Key));
    }

    public function test_update_rejects_more_than_five_attachments(): void
    {
        $this->business->update([
            'admin_attachments' => [
                ['s3_key' => 'key1', 'size' => 1, 'original_filename' => 'a.pdf', 'mime_type' => 'application/pdf'],
                ['s3_key' => 'key2', 'size' => 1, 'original_filename' => 'b.pdf', 'mime_type' => 'application/pdf'],
                ['s3_key' => 'key3', 'size' => 1, 'original_filename' => 'c.pdf', 'mime_type' => 'application/pdf'],
            ],
        ]);

        $files = [
            UploadedFile::fake()->createWithContent('d.pdf', 'd'),
            UploadedFile::fake()->createWithContent('e.pdf', 'e'),
            UploadedFile::fake()->createWithContent('f.pdf', 'f'),
        ];

        $payload = $this->validBusinessPayload();
        $payload['admin_attachments'] = $files;

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $this->business), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('admin_attachments');
    }

    public function test_approved_at_is_set_when_status_changes_to_approved(): void
    {
        $business = BusinessInfo::create([
            'user_id' => null,
            'subdomain_id' => $this->subdomain->id,
            'applicant_type' => 'corporation',
            'business_name' => '審査通過テスト事業者',
            'business_name_kana' => 'シンサツツウカテスト',
            'representative_name' => '代表',
            'representative_name_kana' => 'ダイヒョウ',
            'postal_code' => '664-0001',
            'prefecture' => '兵庫県',
            'city' => '伊丹市',
            'address1' => '荻野2-2-2',
            'phone' => '072-111-2222',
            'email' => 'approved-at-test@example.com',
            'apply' => 0,
            'is_active' => 0,
            'status' => '未着手',
            'qr_only' => false,
        ]);
        $this->assertNull($business->approved_at);

        $payload = $this->validBusinessPayload();
        $payload['status'] = '審査②通過';
        $payload['email'] = 'approved-at-test@example.com';
        $payload['business_name'] = '審査通過テスト事業者';

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.business.update', $business), $payload);

        $response->assertRedirect(route('admin.business.index'));

        $business->refresh();
        $this->assertNotNull($business->approved_at);
        $this->assertSame(now()->toDateString(), $business->approved_at->toDateString());
    }
}
