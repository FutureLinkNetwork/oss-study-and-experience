<?php

namespace Tests\Feature\User;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private Role $userRole;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);

        $this->userRole = Role::factory()->create([
            'name' => 'subdomain_user',
            'level' => 10,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->userRole->id,
            'login_id' => 'user1',
            'name' => '利用者太郎',
            'display_name' => '利用者太郎',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);
    }

    /**
     * 未ログインでは問い合わせ一覧にアクセスできないこと
     */
    public function test_inquiries_index_requires_auth(): void
    {
        $response = $this->get('http://www.localhost/user/inquiries');

        $response->assertRedirect();
    }

    /**
     * ログイン後に問い合わせ一覧が表示されること
     */
    public function test_inquiries_index_displays_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://www.localhost/user/inquiries');

        $response->assertStatus(200);
        $response->assertSee('問い合わせ');
    }

    /**
     * 問い合わせ作成フォームが表示されること
     */
    public function test_inquiries_create_displays_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get('http://www.localhost/user/inquiries/create');

        $response->assertStatus(200);
        $response->assertSee('問い合わせ内容');
    }

    /**
     * 問い合わせ送信が成功しメールが送信されること
     */
    public function test_inquiries_store_succeeds_and_sends_mail(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user)
            ->post('http://www.localhost/user/inquiries', [
                'content' => 'テスト問い合わせ内容です。',
            ]);

        $response->assertRedirect(route('user.inquiries.index'));
        $response->assertSessionHas('success', '問い合わせを送信しました。');

        $this->assertDatabaseHas('inquiries', [
            'user_id' => $this->user->id,
            'content' => 'テスト問い合わせ内容です。',
            'inquiry_type' => InquiryType::User->value,
            'status' => InquiryStatus::Pending->value,
        ]);

        Mail::assertSent(\App\Mail\InquiryReceivedMail::class);
    }

    /**
     * 問い合わせ詳細が表示されること（自分の問い合わせ）
     */
    public function test_inquiries_show_displays_own_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $this->user->id,
            'inquiry_type' => InquiryType::User,
            'content' => '自分の問い合わせ',
            'created_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://www.localhost/user/inquiries/'.$inquiry->id);

        $response->assertStatus(200);
        $response->assertSee('自分の問い合わせ');
    }

    /**
     * 他ユーザーの問い合わせ詳細にはアクセスできないこと
     */
    public function test_inquiries_show_denies_other_users_inquiry(): void
    {
        $otherUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->userRole->id,
            'login_id' => 'user2',
            'name' => '他の利用者',
            'email' => 'other@example.com',
            'is_active' => true,
        ]);

        $inquiry = Inquiry::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'user_id' => $otherUser->id,
            'inquiry_type' => InquiryType::User,
            'content' => '他ユーザーの問い合わせ',
            'created_user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('http://www.localhost/user/inquiries/'.$inquiry->id);

        $response->assertStatus(403);
    }
}
