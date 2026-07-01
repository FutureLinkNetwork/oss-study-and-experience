@extends('layouts.app')

@section('title', '習い事リクエスト詳細 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.course-requests.index') }}" class="hover:text-gray-700">習い事リクエスト管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>習い事リクエスト詳細</span></li>
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

            <!-- 習い事リクエスト詳細フォーム -->
            <form action="{{ route('admin.course-requests.update', $courseRequest) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-clipboard-list text-gray-400 mr-2"></i>
                            習い事リクエスト情報
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 教室名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">教室名</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->classroom_name }}
                                </div>
                            </div>

                            <!-- 教室所在地 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">教室所在地</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->address }}
                                </div>
                            </div>

                            <!-- 教室電話番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">教室電話番号</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->phone }}
                                </div>
                            </div>

                            <!-- お名前 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">お名前</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->requester_name }}
                                </div>
                            </div>

                            <!-- メールアドレス -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">メールアドレス</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->requester_email }}
                                </div>
                            </div>

                            <!-- 電話番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">電話番号</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->requester_phone ?? '未入力' }}
                                </div>
                            </div>

                            <!-- 投稿日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">投稿日</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $courseRequest->created_at->format('Y-m-d H:i') }}
                                </div>
                            </div>
                        </div>

                        <!-- 処理済みラジオボタン -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ステータス</label>
                            <div class="flex gap-6">
                                <label class="flex items-center">
                                    <input type="radio" name="is_confirmed" value="0" 
                                           {{ $courseRequest->is_confirmed == 0 ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">未処理</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="is_confirmed" value="1" 
                                           {{ $courseRequest->is_confirmed == 1 ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">対応中</span>
                                </label>
								<label class="flex items-center">
                                    <input type="radio" name="is_confirmed" value="2" 
                                           {{ $courseRequest->is_confirmed == 2 ? 'checked' : '' }}
                                           class="mr-2 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">処理済み</span>
                                </label>
                            </div>
                        </div>

                        <!-- 備考欄 -->
                        <div class="mt-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                            <textarea id="remarks" name="remarks" rows="4" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('remarks', $courseRequest->remarks) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">最大1000文字まで入力可能です。</p>
                        </div>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="mt-6 flex justify-end space-x-4">
                    <a href="{{ route('admin.course-requests.index') }}" 
                       class="btn-base btn-secondary btn-m">
                        <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
                    </a>
                    <button type="submit" class="btn-base btn-update btn-m">
                        <i class="fas fa-save mr-2"></i>更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection




