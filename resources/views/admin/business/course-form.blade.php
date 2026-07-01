@extends('layouts.app')

@php
    // 変数の安全な初期化
    $course = $course ?? null;
    $isEdit = !is_null($course);
@endphp

@section('title', ($isEdit ? 'コース編集' : 'コース新規登録') . ' - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.business.index') }}" class="hover:text-gray-700">事業者管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.business.edit', $business) }}" class="hover:text-gray-700">{{ $business->business_name }}</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.business.edit-classroom', [$business, $classroom]) }}" class="hover:text-gray-700">{{ $classroom->classroom_name }}</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900 font-medium">
                    {{ $isEdit ? $course->course_name : 'コース新規登録' }}
                </li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- コース情報フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-{{ $isEdit ? 'edit' : 'graduation-cap' }} text-gray-400 mr-2"></i>
                {{ $isEdit ? 'コース情報編集' : 'コース情報入力' }}
            </h2>
        </div>
        
        <form action="{{ $isEdit ? route('admin.business.update-course', [$business, $classroom, $course]) : route('admin.business.store-course', [$business, $classroom]) }}" 
              method="POST" class="p-6">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <!-- コース基本情報 -->
            <div class="field-group">
                <h3 class="text-lg font-medium text-gray-900 mb-4">コース基本情報</h3>
                <div class="form-row cols-1">
                    <div class="md:col-span-2">
                        <label for="course_name" class="field-label required">
                            コース名
                        </label>
                        <input type="text" 
                               class="field-base field-w-100 @error('course_name') error @enderror" 
                               id="course_name" name="course_name" 
                               value="{{ old('course_name', $course?->course_name ?? '') }}" 
                               maxlength="100" required>
                        @error('course_name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 料金・時間・定員 -->
            <div class="border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">料金</h3>
                <div class="form-row cols-1">
                    <div class="field-group">
                        <label for="price" class="field-label required">
                            料金
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" 
                                   class="field-base field-w-100 @error('price') border-red-500 @enderror" 
                                   id="price" name="price" 
                                   value="{{ old('price', $course?->price ?? '') }}" 
                                   min="0" step="1" required>
                        </div>
                        @error('price')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-row cols-1">
                    <div class="field-group">
                        <label for="tax_type" class="field-label required">
                            税区分
                        </label>
                        <div class="mt-1 relative">
                            <input type="radio" name="tax_type" value="inclusive" 
								   {{ old('tax_type', $course?->tax_type ?? '') === 'inclusive' ? 'checked' : '' }} required>
                            <label for="tax_type_inclusive" class="ml-2">内税</label>
							<input type="radio" name="tax_type" value="exclusive" class="ml-6"
								   {{ old('tax_type', $course?->tax_type ?? '') === 'exclusive' ? 'checked' : '' }} required>
                            <label for="tax_type_exclusive" class="ml-2">外税</label>
                        </div>
                        @error('tax_type')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- コース詳細 -->
            <div class="border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">コース詳細</h3>
                <div class="field-group">
                    <label for="course_description" class="field-label required">
                        コース説明
                    </label>
                    <textarea class="field-base field-w-100 @error('course_description') error @enderror" 
                              id="course_description" name="course_description" rows="3">{{ old('course_description', $course?->course_description ?? '') }}</textarea>
                    @error('course_description')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- 対象学年 -->
            @php
                $grades = $grades ?? [];
                $selectedGrades = old('grades', $course?->grades ?? []);
                if (!is_array($selectedGrades)) {
                    $selectedGrades = [];
                }
            @endphp
            @if(count($grades) > 0)
            <div class="border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">対象学年</h3>
                <div class="field-group">
                    <label class="field-label required">学年（複数選択可）</label>
                    <div class="space-y-2">
                        @foreach($grades as $grade)
                            <div class="field-checkbox">
                                <input type="checkbox" 
                                       name="grades[]" 
                                       id="grade_{{ $loop->index }}"
                                       value="{{ $grade }}"
                                       class="field-checkbox-input"
                                       {{ in_array($grade, $selectedGrades) ? 'checked' : '' }}>
                                <label for="grade_{{ $loop->index }}" class="field-checkbox-label">{{ $grade }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('grades')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    @error('grades.*')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            @endif

            <!-- コース期間設定-->
            <div class="border-gray-200 pt-6 cols-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4 ">コース期間設定</h3>
				<div class="form-row cols-2">
                <div class="field-group">
                    <label for="open_date" class="field-label">
                        開始日
                    </label>
                    <input type="date" class="field-base field-w-100 @error('open_date') error @enderror" 
                           id="open_date" name="open_date" 
                           value="{{ old('open_date', $course?->open_date?->format('Y-m-d') ?? '') }}" style="max-width: 200px;">
                    @error('open_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="field-group">
                    <label for="end_date" class="field-label">
                        終了日
                    </label>
                    <input type="date" class="field-base field-w-100 @error('end_date') error @enderror" 
                           id="end_date" name="end_date" 
                           value="{{ old('end_date', $course?->end_date?->format('Y-m-d') ?? '') }}" style="max-width: 200px;">
                    @error('end_date')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
				</div>
            </div>

            <!-- コース状態 -->
            <div class="field-group">
				<h3 class="text-lg font-medium text-gray-900 mb-4 ">コース状態</h3>
                <div class="field-checkbox">
                    <input type="checkbox" name="is_active" id="is_active" value="1" 
                           {{ old('is_active', $course?->is_active ?? 1) ? 'checked' : '' }}
                           class="field-checkbox-input">
                    <label for="is_active" class="field-checkbox-label">有効</label>
                </div>
            </div>

			<!-- フォームアクション -->
            <div class="border-t border-gray-200 pt-6">
                <div class="flex justify-between">
                    <a href="{{ route('admin.business.edit-classroom', [$business, $classroom]) }}" 
                       class="btn-base btn-back btn-m">
                        <i class="fas fa-arrow-left mr-2"></i> 戻る
                    </a>
                    <button type="submit" 
                            class="btn-base {{ $isEdit ? 'btn-update' : 'btn-create' }} btn-m">
                        <i class="fas fa-save mr-2"></i> {{ $isEdit ? '更新' : '登録' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection