<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseCategoryController extends Controller
{
    /**
     * 習い事種別管理画面
     */
    public function index(Request $request)
    {
        // ログインユーザーのサブドメインIDを取得
        $user = Auth::user();
        $selectedSubdomainId = $user->subdomain_id;

        $parentCategories = [];
        if ($selectedSubdomainId) {
            $parentCategories = CourseParentCategory::with(['activeCategories'])
                ->forSubdomain($selectedSubdomainId)
                ->active()
                ->ordered()
                ->get();
        }

        return view('admin.course-category.index', compact(
            'selectedSubdomainId',
            'parentCategories',
            'user'
        ));
    }

    /**
     * 親分類作成
     */
    public function storeParentCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $userId = Auth::id();
            $subdomainId = Auth::user()->subdomain_id;
            $maxSortOrder = CourseParentCategory::forSubdomain($subdomainId)->max('sort_order') ?? 0;
            $sortOrder = array_key_exists('sort_order', $validated) && $validated['sort_order'] !== null
                ? (int) $validated['sort_order']
                : $maxSortOrder + 1;

            $parentCategory = CourseParentCategory::create([
                'subdomain_id' => $subdomainId,
                'name' => $validated['name'],
                'sort_order' => $sortOrder,
                'created_user_id' => $userId,
                'updated_user_id' => $userId,
            ]);

            $parentCategory->load(['activeCategories']);

            return response()->json([
                'success' => true,
                'message' => '親分類を作成しました。',
                'data' => $parentCategory,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '親分類の作成に失敗しました。',
            ], 500);
        }
    }

    /**
     * 親分類更新
     */
    public function updateParentCategory(Request $request, CourseParentCategory $parentCategory): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'sort_order' => 'required|integer|min:0',
            ]);

            $parentCategory->update([
                'name' => $validated['name'],
                'sort_order' => $validated['sort_order'],
                'updated_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '親分類を更新しました。',
                'data' => $parentCategory,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '親分類の更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * 親分類削除（論理削除）
     */
    public function destroyParentCategory(CourseParentCategory $parentCategory): JsonResponse
    {
        try {
            DB::transaction(function () use ($parentCategory) {
                // 子分類も同時に論理削除
                $parentCategory->categories()->update([
                    'is_active' => false,
                    'updated_user_id' => Auth::id(),
                ]);

                // 親分類を論理削除
                $parentCategory->update([
                    'is_active' => false,
                    'updated_user_id' => Auth::id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => '親分類を削除しました。',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '親分類の削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * 分類作成
     */
    public function storeCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_category_id' => 'required|exists:course_categories_parent,id',
                'name' => 'required|string|max:100',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $userId = Auth::id();
            $subdomainId = Auth::user()->subdomain_id;
            $maxSortOrder = CourseCategory::forParent($validated['parent_category_id'])->max('sort_order') ?? 0;
            $sortOrder = array_key_exists('sort_order', $validated) && $validated['sort_order'] !== null
                ? (int) $validated['sort_order']
                : $maxSortOrder + 1;

            $category = CourseCategory::create([
                'subdomain_id' => $subdomainId,
                'parent_category_id' => $validated['parent_category_id'],
                'name' => $validated['name'],
                'sort_order' => $sortOrder,
                'created_user_id' => $userId,
                'updated_user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => '分類を作成しました。',
                'data' => $category,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類の作成に失敗しました。',
            ], 500);
        }
    }

    /**
     * 分類更新
     */
    public function updateCategory(Request $request, CourseCategory $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'sort_order' => 'required|integer|min:0',
            ]);

            $category->update([
                'name' => $validated['name'],
                'sort_order' => $validated['sort_order'],
                'updated_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '分類を更新しました。',
                'data' => $category,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類の更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * 分類削除（論理削除）
     */
    public function destroyCategory(CourseCategory $category): JsonResponse
    {
        try {
            $category->update([
                'is_active' => false,
                'updated_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '分類を削除しました。',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '分類の削除に失敗しました。',
            ], 500);
        }
    }
}
