@extends('layouts.app')

@section('title', '習い事種別管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>習い事種別管理</span></li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

    @if($selectedSubdomainId && $parentCategories->count() > 0)
        <!-- 種別一覧 -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-list text-gray-400 mr-2"></i>
                    種別一覧
                </h2>
                <button type="button" id="addParentCategoryBtn" class="btn-base btn-create btn-m">
                    <i class="fas fa-plus mr-2"></i>親分類を追加
                </button>
            </div>
            <div class="p-6">
                <div id="categoryTree" class="space-y-4">
                    @foreach($parentCategories as $parentCategory)
                        <div class="parent-category border border-gray-200 rounded-lg" data-parent-id="{{ $parentCategory->id }}">
                            <!-- 親分類 -->
                            <div class="parent-category-header bg-gray-50 px-4 py-3 flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-folder text-blue-500"></i>
                                    <span class="text-gray-500 text-sm">並び順: {{ $parentCategory->sort_order }}</span>
                                    <span class="parent-category-name font-medium text-gray-900">{{ $parentCategory->name }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="btn-base btn-update btn-xs edit-parent-btn" data-parent-id="{{ $parentCategory->id }}" data-parent-name="{{ $parentCategory->name }}" data-parent-sort-order="{{ $parentCategory->sort_order }}">
                                        編集
                                    </button>
                                    <button class="btn-base btn-disable btn-xs delete-parent-btn" data-parent-id="{{ $parentCategory->id }}" data-parent-name="{{ $parentCategory->name }}">
                                        無効化
                                    </button>
                                    <button class="btn-base btn-create btn-xs add-category-btn" data-parent-id="{{ $parentCategory->id }}">
                                        <i class="fas fa-plus mr-1"></i>分類追加
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 子分類 -->
                            <div class="categories-list p-4">
                                @if($parentCategory->activeCategories->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($parentCategory->activeCategories as $category)
                                            <div class="category-item bg-white border border-gray-200 rounded-lg p-3 hover:bg-gray-50" data-category-id="{{ $category->id }}">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2">
                                                        <i class="fas fa-tag text-green-500 text-sm"></i>
                                                        <span class="text-gray-400 text-xs">並び順: {{ $category->sort_order }}</span>
                                                        <span class="category-name text-sm font-medium text-gray-700">{{ $category->name }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-1">
                                                        <button class="btn-base btn-update btn-xs edit-category-btn" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" data-category-sort-order="{{ $category->sort_order }}">
                                                            編集
                                                        </button>
                                                        <button class="btn-base btn-disable btn-xs delete-category-btn" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}">
                                                            無効化
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4 text-gray-500">
                                        <i class="fas fa-info-circle mb-2"></i>
                                        <p>分類が登録されていません</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif($selectedSubdomainId)
        <!-- 空の状態 -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-12 text-center">
                <i class="fas fa-list-alt text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">種別が登録されていません</h3>
                <p class="text-gray-500 mb-6">最初の親分類を追加してください。</p>
                <button id="addFirstParentCategoryBtn" class="btn-base btn-create btn-m">
                    <i class="fas fa-plus mr-2"></i>最初の親分類を追加
                </button>
            </div>
        </div>
    @else
        <!-- サブドメイン未選択 -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-12 text-center">
                <i class="fas fa-arrow-up text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">サブドメインを選択してください</h3>
                <p class="text-gray-500">種別を管理するサブドメインを上記から選択してください。</p>
            </div>
        </div>
    @endif
</div>

<!-- 親分類追加/編集モーダル -->
<div id="parentCategoryModal" class="fixed inset-0 hidden z-50" style="z-index: 9999; background-color: rgba(107, 114, 128, 0.2) !important;">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full" style="margin-top: 0; margin-bottom: 0;">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="parentModalTitle" class="text-lg font-medium text-gray-900">親分類追加</h3>
            </div>
            <form id="parentCategoryForm" novalidate>
                <div class="p-6">
                    <div class="field-group">
                        <label for="parentCategoryName" class="field-label required">親分類名</label>
                        <input type="text" id="parentCategoryName" name="name" class="field-base field-w-100" maxlength="100" required>
                        <div id="parentNameError" class="field-error hidden"></div>
                    </div>
                    <div class="field-group mt-4">
                        <label for="parentSortOrder" class="field-label">並び順</label>
                        <input type="number" id="parentSortOrder" name="sort_order" class="field-base field-w-100" min="0" step="1" placeholder="未入力の場合は末尾に追加">
                        <div id="parentSortOrderError" class="field-error hidden"></div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                    <button type="button" id="cancelParentBtn" class="btn-base btn-back btn-m">
                        キャンセル
                    </button>
                    <button type="submit" id="saveParentBtn" class="btn-base btn-create btn-m">
                        <i class="fas fa-save mr-2"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 分類追加/編集モーダル -->
<div id="categoryModal" class="fixed inset-0 hidden z-50" style="z-index: 9999; background-color: rgba(107, 114, 128, 0.2) !important;">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full" style="margin-top: 0; margin-bottom: 0;">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="categoryModalTitle" class="text-lg font-medium text-gray-900">分類追加</h3>
            </div>
            <form id="categoryForm" novalidate>
                <div class="p-6">
                    <div class="field-group">
                        <label for="categoryName" class="field-label required">分類名</label>
                        <input type="text" id="categoryName" name="name" class="field-base field-w-100" maxlength="100" required>
                        <div id="categoryNameError" class="field-error hidden"></div>
                    </div>
                    <div class="field-group mt-4">
                        <label for="categorySortOrder" class="field-label">並び順</label>
                        <input type="number" id="categorySortOrder" name="sort_order" class="field-base field-w-100" min="0" step="1" placeholder="未入力の場合は末尾に追加">
                        <div id="categorySortOrderError" class="field-error hidden"></div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                    <button type="button" id="cancelCategoryBtn" class="btn-base btn-back btn-m">
                        キャンセル
                    </button>
                    <button type="submit" id="saveCategoryBtn" class="btn-base btn-create btn-m">
                        <i class="fas fa-save mr-2"></i>保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* モーダル用の軽い背景スタイル */
#parentCategoryModal,
#categoryModal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    z-index: 9999 !important;
    background-color: rgba(107, 114, 128, 0.15) !important;
    backdrop-filter: blur(1px) !important;
}

#parentCategoryModal .bg-white,
#categoryModal .bg-white {
    background: white !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25) !important;
    max-width: 28rem !important;
    width: 100% !important;
    margin: 1rem !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
}

.hidden {
    display: none !important;
}

/* フォーカス時のアウトライン調整 */
#parentCategoryModal input:focus,
#categoryModal input:focus {
    outline: 2px solid #3b82f6 !important;
    outline-offset: 2px !important;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin/course-category.js') }}"></script>
@endpush