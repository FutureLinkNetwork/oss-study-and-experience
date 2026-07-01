<?php

namespace Tests\Feature\Admin;

use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private Subdomain $subdomain;

    private Role $adminRole;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_course_categories_index_displays_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.course-categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.course-category.index');
    }

    public function test_store_parent_category_with_sort_order_uses_given_value(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.parent-categories.store'), [
                'name' => '親A',
                'sort_order' => 5,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 5);
        $this->assertDatabaseHas('course_categories_parent', [
            'subdomain_id' => $this->subdomain->id,
            'name' => '親A',
            'sort_order' => 5,
        ]);
    }

    public function test_store_parent_category_without_sort_order_uses_max_plus_one(): void
    {
        CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '既存親',
            'sort_order' => 3,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.parent-categories.store'), [
                'name' => '親B',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 4);
        $this->assertDatabaseHas('course_categories_parent', [
            'subdomain_id' => $this->subdomain->id,
            'name' => '親B',
            'sort_order' => 4,
        ]);
    }

    public function test_update_parent_category_updates_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.course-categories.parent-categories.update', $parent), [
                'name' => '親（改名）',
                'sort_order' => 10,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 10);
        $parent->refresh();
        $this->assertSame(10, $parent->sort_order);
        $this->assertSame('親（改名）', $parent->name);
    }

    public function test_update_parent_category_rejects_missing_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.course-categories.parent-categories.update', $parent), [
                'name' => '親',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_order']);
    }

    public function test_store_parent_category_rejects_negative_sort_order(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.parent-categories.store'), [
                'name' => '親',
                'sort_order' => -1,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_order']);
    }

    public function test_update_parent_category_rejects_negative_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.course-categories.parent-categories.update', $parent), [
                'name' => '親',
                'sort_order' => -1,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_order']);
    }

    public function test_store_category_with_sort_order_uses_given_value(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 0,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.categories.store'), [
                'parent_category_id' => $parent->id,
                'name' => '小分類A',
                'sort_order' => 2,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 2);
        $this->assertDatabaseHas('course_categories', [
            'parent_category_id' => $parent->id,
            'name' => '小分類A',
            'sort_order' => 2,
        ]);
    }

    public function test_store_category_without_sort_order_uses_max_plus_one(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 0,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);
        CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parent->id,
            'name' => '既存小',
            'sort_order' => 7,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.categories.store'), [
                'parent_category_id' => $parent->id,
                'name' => '小分類B',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 8);
        $this->assertDatabaseHas('course_categories', [
            'parent_category_id' => $parent->id,
            'name' => '小分類B',
            'sort_order' => 8,
        ]);
    }

    public function test_update_category_updates_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 0,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);
        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parent->id,
            'name' => '小',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.course-categories.categories.update', $category), [
                'name' => '小（改名）',
                'sort_order' => 99,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sort_order', 99);
        $category->refresh();
        $this->assertSame(99, $category->sort_order);
        $this->assertSame('小（改名）', $category->name);
    }

    public function test_update_category_rejects_missing_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 0,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);
        $category = CourseCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'parent_category_id' => $parent->id,
            'name' => '小',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson(route('admin.course-categories.categories.update', $category), [
                'name' => '小',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_order']);
    }

    public function test_store_category_rejects_negative_sort_order(): void
    {
        $parent = CourseParentCategory::create([
            'subdomain_id' => $this->subdomain->id,
            'name' => '親',
            'sort_order' => 0,
            'is_active' => true,
            'created_user_id' => $this->adminUser->id,
            'updated_user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.course-categories.categories.store'), [
                'parent_category_id' => $parent->id,
                'name' => '小',
                'sort_order' => -1,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_order']);
    }
}
