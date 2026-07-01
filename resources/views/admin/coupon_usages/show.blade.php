@extends('layouts.app')

@section('title', 'クーポン利用詳細 - '.($subdomain->system_name ?? ''))

@section('content')
<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><a href="{{ route('admin.coupon-usages.index') }}" class="hover:text-gray-700">クーポンの利用状況管理</a></li>
                <li><span>/</span></li>
                <li><span>利用詳細</span></li>
            </ol>
        </nav>

        <div class="mt-4">
            <h1 class="text-2xl font-bold text-gray-900">クーポン利用詳細</h1>

            @if(session('success'))
                <div class="mt-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mt-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">利用内容</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">利用日</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->used_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">利用者（児童名）</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->user?->beneficiary?->child_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">教室名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->classroomInfo?->classroom_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">事業者名</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->businessInfo?->business_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">金額</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($voucherUsage->amount) }}円</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">キャンセル</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->is_cancelled ? 'キャンセル済' : '未キャンセル' }}</dd>
                        </div>
                        @if($voucherUsage->admin_corrected_at)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">管理者修正日</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $voucherUsage->admin_corrected_at->format('Y-m-d H:i') }}</dd>
                            </div>
                        @endif
                        @if($voucherUsage->admin_correction_memo)
                            <div class="md:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">修正メモ</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $voucherUsage->admin_correction_memo }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            @if($isEditable)
                <div class="bg-white shadow rounded-lg mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">編集（先月分以降のみ編集可能）</h2>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('admin.coupon-usages.update', $voucherUsage) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                <div>
                                    <label for="used_at" class="block text-sm font-medium text-gray-700 mb-1">利用日 <span class="text-red-500">*</span></label>
                                    <input type="date" id="used_at" name="used_at" value="{{ old('used_at', $voucherUsage->used_at?->format('Y-m-d')) }}"
                                           min="{{ $editableFrom->format('Y-m-d') }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                           required>
                                </div>
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">金額（円） <span class="text-red-500">*</span></label>
                                    <input type="number" id="amount" name="amount" min="1" value="{{ old('amount', $voucherUsage->amount) }}"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                           required>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="hidden" name="is_cancelled" value="0">
                                        <input type="checkbox" name="is_cancelled" value="1" {{ old('is_cancelled', false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-700">キャンセルする</span>
                                    </label>
                                </div>
                                <div>
                                    <label for="admin_correction_memo" class="block text-sm font-medium text-gray-700 mb-1">修正メモ <span class="text-red-500">*</span></label>
                                    <textarea id="admin_correction_memo" name="admin_correction_memo" rows="4" maxlength="1000"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                              placeholder="修正内容を記入してください（必須）"
                                              required>{{ old('admin_correction_memo', $voucherUsage->admin_correction_memo ?? '') }}</textarea>
                                    <p class="mt-1 text-xs text-gray-500">1000文字以内</p>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-4">
                                <a href="{{ route('admin.coupon-usages.index') }}" class="btn-base btn-back btn-m">一覧に戻る</a>
                                <button type="submit" class="btn-base btn-create btn-m">保存</button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <div class="mt-6">
                    <a href="{{ route('admin.coupon-usages.index') }}" class="btn-base btn-back btn-m">一覧に戻る</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
