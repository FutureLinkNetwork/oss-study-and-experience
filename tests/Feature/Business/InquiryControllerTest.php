<?php

namespace Tests\Feature\Business;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
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

    private Role $businessRole;

    private User $businessUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdomain = Subdomain::factory()->create([
            'subdomain' => 'www',
            'is_active' => true,
        ]);

        $this->businessRole = Role::factory()->create([
            'name' => 'subdomain_business',
            'level' => 20,
            'is_active' => true,
        ]);

        $this->businessUser = User::factory()->create([
            'subdomain_id' => $this->subdomain->id,
            'role_id' => $this->businessRole->id,
            'login_id' => 'business1',
            'name' => '事業者太郎',
            'display_name' => '事業者太郎',
            'email' => 'business@example.com',
            'is_active' => true,
        ]);
    }

    /**
     * 未ログインでは問い合わせ一覧にアクセスできないこと
     */
    public function test_inquiries_index_requires_auth(): void
    {
        $response = $this->get('http://www.localhost/business/inquiries');

        $response->assertRedirect();
    }

    /**
     * ログイン後に問い合わせ一覧が表示されること
     */
    public function test_inquiries_index_displays_for_authenticated_business(): void
    {
        $response = $this->actingAs($this->businessUser)
            ->get('http://www.localhost/business/inquiries');

        $response->assertStatus(200);
        $response->assertSee('問い合わせ');
    }

    /**
     * 問い合わせ送信が成功すること
     */
    public function test_inquiries_store_succeeds(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->businessUser)
            ->post('http://www.localhost/business/inquiries', [
                'content' => '事業者からの問い合わせです。',
            ]);

        $response->assertRedirect(route('business.inquiries.index'));
        $response->assertSessionHas('success', '問い合わせを送信しました。');

        $this->assertDatabaseHas('inquiries', [
            'user_id' => $this->businessUser->id,
            'content' => '事業者からの問い合わせです。',
            'inquiry_type' => InquiryType::Business->value,
            'status' => InquiryStatus::Pending->value,
        ]);

        Mail::assertSent(\App\Mail\InquiryReceivedMail::class);
    }
}
