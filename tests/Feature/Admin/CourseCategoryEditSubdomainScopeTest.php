<?php

namespace Tests\Feature\Admin;

use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCategoryEditSubdomainScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     0: Subdomain,
     *     1: User,
     *     2: CourseParentCategory,
     *     3: CourseParentCategory,
     *     4: CourseCategory,
     *     5: CourseCategory
     * }
     */
    private function createScopedFixtures(): array
    {
        $currentSubdomain = Subdomain::factory()->create([
            'subdomain' => 'current-course-category',
            'is_active' => true,
        ]);

        $otherSubdomain = Subdomain::factory()->create([
            'subdomain' => 'other-course-category',
            'is_active' => true,
        ]);

        $adminRole = Role::factory()->create([
            'name' => 'subdomain_admin',
            'level' => 80,
            'is_active' => true,
        ]);

        $adminUser = User::factory()->create([
            'subdomain_id' => $currentSubdomain->id,
            'role_id' => $adminRole->id,
            'login_id' => 'admin_course_category_scope',
            'is_active' => true,
        ]);

        $sameParent = CourseParentCategory::create([
            'subdomain_id' => $currentSubdomain->id,
            'name' => '同一サブドメイン親分類',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $adminUser->id,
            'updated_user_id' => $adminUser->id,
        ]);

        $otherParent = CourseParentCategory::create([
            'subdomain_id' => $otherSubdomain->id,
            'name' => '他サブドメイン親分類',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $adminUser->id,
            'updated_user_id' => $adminUser->id,
        ]);

        $sameCategory = CourseCategory::create([
            'subdomain_id' => $currentSubdomain->id,
            'parent_category_id' => $sameParent->id,
            'name' => '同一サブドメイン種別',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $adminUser->id,
            'updated_user_id' => $adminUser->id,
        ]);

        $otherCategory = CourseCategory::create([
            'subdomain_id' => $otherSubdomain->id,
            'parent_category_id' => $otherParent->id,
            'name' => '他サブドメイン種別',
            'sort_order' => 1,
            'is_active' => true,
            'created_user_id' => $adminUser->id,
            'updated_user_id' => $adminUser->id,
        ]);

        return [$currentSubdomain, $adminUser, $sameParent, $otherParent, $sameCategory, $otherCategory];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function url(string $name, array $params = []): string
    {
        return 'http://current-course-category.localhost'.route($name, $params, false);
    }

    public function test_update_parent_rejects_from_another_subdomain(): void
    {
        [, $adminUser, , $otherParent] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->putJson($this->url('admin.course-categories.parent-categories.update', [$otherParent]), [
                'name' => '改ざんされた親分類',
                'sort_order' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('course_categories_parent', [
            'id' => $otherParent->id,
            'name' => '他サブドメイン親分類',
        ]);
    }

    public function test_destroy_parent_rejects_from_another_subdomain(): void
    {
        [, $adminUser, , $otherParent] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->deleteJson($this->url('admin.course-categories.parent-categories.destroy', [$otherParent]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('course_categories_parent', [
            'id' => $otherParent->id,
            'is_active' => true,
        ]);
    }

    public function test_update_category_rejects_from_another_subdomain(): void
    {
        [, $adminUser, , , , $otherCategory] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->putJson($this->url('admin.course-categories.categories.update', [$otherCategory]), [
                'name' => '改ざんされた種別',
                'sort_order' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('course_categories', [
            'id' => $otherCategory->id,
            'name' => '他サブドメイン種別',
        ]);
    }

    public function test_destroy_category_rejects_from_another_subdomain(): void
    {
        [, $adminUser, , , , $otherCategory] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->deleteJson($this->url('admin.course-categories.categories.destroy', [$otherCategory]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('course_categories', [
            'id' => $otherCategory->id,
            'is_active' => true,
        ]);
    }

    public function test_update_parent_allows_in_current_subdomain(): void
    {
        [, $adminUser, $sameParent] = $this->createScopedFixtures();

        $response = $this->actingAs($adminUser)
            ->putJson($this->url('admin.course-categories.parent-categories.update', [$sameParent]), [
                'name' => '更新された親分類',
                'sort_order' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('course_categories_parent', [
            'id' => $sameParent->id,
            'name' => '更新された親分類',
        ]);
    }
}
