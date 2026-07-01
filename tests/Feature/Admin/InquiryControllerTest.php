<?php

namespace Tests\Feature\Admin;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private Role $adminRole;

    private User $adminUser;

    private User $senderUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);

        $this->adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 60,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->adminRole->id,
            'login_id' => 'admin1',
            'name' => '管理者',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $this->senderUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $userRole->id,
            'login_id' => 'user1',
            'name' => '送信者',
            'email' => 'sender@example.com',
            'is_active' => true,
        ]);
    }

    /**
     * 問い合わせ一覧が表示されること
     */
    public function test_inquiries_index_displays_for_admin(): void
    {
        Inquiry::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->senderUser->id,
            'inquiry_type' => InquiryType::User,
            'content' => '問い合わせ内容',
            'created_user_id' => $this->senderUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://www.localhost/admin/inquiries');

        $response->assertStatus(200);
        $response->assertSee('問い合わせ一覧');
    }

    /**
     * 問い合わせ詳細が表示されること
     */
    public function test_inquiries_show_displays_for_admin(): void
    {
        $inquiry = Inquiry::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->senderUser->id,
            'inquiry_type' => InquiryType::User,
            'content' => '問い合わせ内容',
            'created_user_id' => $this->senderUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('http://www.localhost/admin/inquiries/'.$inquiry->id);

        $response->assertStatus(200);
        $response->assertSee('問い合わせ内容');
    }

    /**
     * 問い合わせ更新が成功すること
     */
    public function test_inquiries_update_succeeds(): void
    {
        $inquiry = Inquiry::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->senderUser->id,
            'inquiry_type' => InquiryType::User,
            'content' => '問い合わせ内容',
            'created_user_id' => $this->senderUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put('http://www.localhost/admin/inquiries/'.$inquiry->id, [
                'status' => InquiryStatus::InProgress->value,
                'remarks' => '備考',
            ]);

        $response->assertRedirect(route('admin.inquiries.show', $inquiry));
        $response->assertSessionHas('success', '問い合わせを更新しました。');

        $inquiry->refresh();
        $this->assertSame(InquiryStatus::InProgress, $inquiry->status);
        $this->assertSame('備考', $inquiry->remarks);
    }
}
