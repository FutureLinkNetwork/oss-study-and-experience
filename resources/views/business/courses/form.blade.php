@extends('layouts.app')

@php
    $isEdit = isset($course);
    $formCourse = $course ?? $sourceCourse;
@endphp
@section('title', ($isEdit ? 'コース編集' : 'コース作成') . ' - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('business.courses.index') }}" class="hover:text-gray-700">コース管理</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>{{ $isEdit ? 'コース編集' : 'コース新規登録' }}</span></li>
            </ol>
        </nav>
	<!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            <!-- 複製元情報表示（新規作成時のみ） -->
            @if(!$isEdit && $sourceCourse)
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">複製元コース</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p><strong>コース名:</strong> {{ $sourceCourse->course_name }}</p>
                                <p><strong>料金:</strong> ¥{{ number_format($sourceCourse->price) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- エラーメッセージ -->
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">入力にエラーがあります</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- コースフォーム -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ $isEdit ? 'コース情報編集' : 'コース情報' }}</h3>
                </div>

                <form method="POST" action="{{ $isEdit ? route('business.courses.update', $course) : route('business.courses.store') }}" class="px-6 py-4 space-y-6">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <!-- 教室選択 -->
                    <div>
                        <label for="classroom_info_id" class="block text-sm font-medium text-gray-700 mb-2">
                            教室 <span class="text-red-500">*</span>
                        </label>
                        <select id="classroom_info_id"
                                name="classroom_info_id"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('classroom_info_id') border-red-300 @enderror">
                            <option value="">教室を選択してください</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}"
                                        {{ old('classroom_info_id', $formCourse?->classroom_info_id) == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->classroom_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classroom_info_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- コース名 -->
                    <div>
                        <label for="course_name" class="block text-sm font-medium text-gray-700 mb-2">
                            コース名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="course_name"
                               name="course_name"
                               required
                               maxlength="100"
                               value="{{ old('course_name', $formCourse?->course_name ?? '') }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('course_name') border-red-300 @enderror"
                               placeholder="コース名を入力してください">
                        @error('course_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- コース説明 -->
                    <div>
                        <label for="course_description" class="block text-sm font-medium text-gray-700 mb-2">
                            コース説明
                        </label>
                        <textarea id="course_description"
                                  name="course_description"
                                  rows="4"
                                  maxlength="1000"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('course_description') border-red-300 @enderror"
                                  placeholder="コースの内容や特徴を説明してください">{{ old('course_description', $formCourse?->course_description ?? '') }}</textarea>
                        @error('course_description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 対象学年 -->
                    @php
                        $grades = $grades ?? [];
                        $selectedGrades = old('grades', $formCourse?->grades ?? []);
                        if (!is_array($selectedGrades)) {
                            $selectedGrades = [];
                        }
                    @endphp
                    @if(count($grades) > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            対象学年 <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2 border border-gray-300 rounded-md p-4">
                            @foreach($grades as $grade)
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           name="grades[]"
                                           value="{{ $grade }}"
                                           class="form-checkbox text-green-600"
                                           {{ in_array($grade, $selectedGrades) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">{{ $grade }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('grades')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('grades.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <!-- 料金 -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            料金（円） <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="price"
                               name="price"
                               required
                               min="0"
                               value="{{ old('price', $formCourse?->price ?? '') }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('price') border-red-300 @enderror"
                               placeholder="料金を入力してください">
                        @error('price')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 税区分 -->
                    <div>
                        <label for="tax_type" class="block text-sm font-medium text-gray-700 mb-2">
                            税区分
                        </label>
                        <select id="tax_type"
                                name="tax_type"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('tax_type') border-red-300 @enderror">
                            <option value="">選択してください</option>
                            <option value="inclusive" {{ old('tax_type', $formCourse?->tax_type ?? '') == 'inclusive' ? 'selected' : '' }}>税込</option>
                            <option value="exclusive" {{ old('tax_type', $formCourse?->tax_type ?? '') == 'exclusive' ? 'selected' : '' }}>外税</option>
                        </select>
                        @error('tax_type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 開催期間 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="open_date" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $isEdit ? '開始日' : '表示開始日' }}
                            </label>
                            <input type="date"
                                   id="open_date"
                                   name="open_date"
                                   value="{{ old('open_date', $formCourse && $formCourse->open_date ? $formCourse->open_date->format('Y-m-d') : '') }}"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('open_date') border-red-300 @enderror">
                            @error('open_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $isEdit ? '終了日' : '表示終了日' }}
                            </label>
                            <input type="date"
                                   id="end_date"
                                   name="end_date"
                                   value="{{ old('end_date', $formCourse && $formCourse->end_date ? $formCourse->end_date->format('Y-m-d') : '') }}"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm @error('end_date') border-red-300 @enderror">
                            @error('end_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @if(!$isEdit)
						<p class="mt-2 text-sm notice">※未設定時は無期限になります</p>
                        @endif
                    </div>

                    <!-- 状態 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            コースの状態
                        </label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center">
                                <input type="radio"
                                       name="is_active"
                                       value="1"
                                       class="form-radio text-green-600"
                                       {{ old('is_active', $formCourse?->is_active ?? true) == 1 ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">
                                    <i class="fas fa-check-circle text-green-600 mr-1"></i>有効
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio"
                                       name="is_active"
                                       value="0"
                                       class="form-radio text-red-600"
                                       {{ old('is_active', $formCourse?->is_active ?? true) == 0 ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">
                                    <i class="fas fa-times-circle text-red-600 mr-1"></i>無効
                                </span>
                            </label>
                        </div>
                        @error('is_active')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ボタン -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('business.courses.index') }}"
                           class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md text-sm font-medium">
                            キャンセル
                        </a>
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-save mr-2"></i>{{ $isEdit ? '更新する' : 'コースを作成' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
