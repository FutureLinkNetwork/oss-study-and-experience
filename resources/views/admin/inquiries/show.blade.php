@extends('layouts.app')

@section('title', '問い合わせ詳細 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex flex-wrap gap-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span>/</span></li>
                <li><a href="{{ route('admin.inquiries.index') }}" class="hover:text-gray-700">問い合わせ管理（利用者・事業者）</a></li>
                <li><span>/</span></li>
                <li><span>詳細</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            @if(session('success'))
                <div class="alert-base alert-success alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('success') }}
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert-base alert-error alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('error') }}
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert-base alert-error alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-message">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <form action="{{ route('admin.inquiries.update', $inquiry) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-envelope text-gray-400 mr-2"></i>
                            問い合わせ情報
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        @php
                            $phone = null;
                            $detailUrl = null;
                            $detailLabel = null;
                            if ($inquiry->inquiry_type->value === 'user' && $inquiry->user?->beneficiary) {
                                $phone = $inquiry->user->beneficiary->guardian_phone;
                                $detailUrl = route('admin.beneficiaries.show', $inquiry->user->beneficiary);
                                $detailLabel = '利用者詳細ページ';
                            }
                            if ($inquiry->inquiry_type->value === 'business') {
                                $businessInfo = $inquiry->user?->businessInfos?->first();
                                if ($businessInfo) {
                                    $phone = $businessInfo->phone ?? $businessInfo->contact_phone;
                                    $detailUrl = route('admin.business.edit', $businessInfo);
                                    $detailLabel = '事業者詳細ページ';
                                }
                            }
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">問い合わせ日時</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $inquiry->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">種別</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $inquiry->inquiry_type->label() }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">利用者／事業者名</label>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $inquiry->sender_name }}
                                </div>
                                @if($detailUrl && $detailLabel)
                                <a href="{{ $detailUrl }}" class="btn-base btn-update btn-m inline-flex items-center" target="_blank" rel="noopener noreferrer">
                                    {{ $detailLabel }} <i class="fas fa-external-link-alt text-xs ml-1"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">メールアドレス</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $inquiry->user->email ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">電話番号</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $phone ?? '—' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">問い合わせ内容</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900 whitespace-pre-wrap">{{ $inquiry->content }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                            <div class="flex flex-wrap gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="pending"
                                           {{ old('status', $inquiry->status->value) === 'pending' ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">未処理</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="in_progress"
                                           {{ old('status', $inquiry->status->value) === 'in_progress' ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">対応中</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status" value="completed"
                                           {{ old('status', $inquiry->status->value) === 'completed' ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">処理済み</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">備考（管理者用）</label>
                            <textarea id="remarks" name="remarks" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm @error('remarks') border-red-500 @enderror">{{ old('remarks', $inquiry->remarks) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">最大1000文字まで入力可能です。</p>
                            @error('remarks')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex flex-wrap justify-end gap-4">
                        <a href="{{ route('admin.inquiries.index') }}"
                           class="btn-base btn-back btn-m">
                            <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
                        </a>
                        <button type="submit" class="btn-base btn-update btn-m">
                            <i class="fas fa-save mr-2"></i>更新
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
