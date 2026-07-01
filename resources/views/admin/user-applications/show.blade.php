@extends('layouts.app')

@section('title', '利用者申請詳細 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.user-applications.index') }}" class="hover:text-gray-700">利用者申請管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>利用者申請詳細</span></li>
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

            <!-- 利用者申請詳細 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-file-alt text-gray-400 mr-2"></i>
                        利用者申請情報
                    </h2>
                </div>
                <div class="p-6">
                    <!-- 申請日 -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">申請日</label>
                        <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                            {{ $userApplication->created_at->format('Y-m-d H:i:s') }}
                        </div>
                    </div>

                    <!-- 必須項目セクション -->
                    <div class="mb-8">
                        <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">必須項目</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 就学援助認定番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">就学援助認定番号</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->certification_number }}
                                </div>
                            </div>

                            <!-- 就学援助認定者名（保護者名） -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">保護者名（就学援助受給者）</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->guardian_name }}
                                </div>
                            </div>

                            <!-- 就学援助認定者名カナ（保護者名） -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">保護者名カナ（就学援助受給者）</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->guardian_name_kana ?? '' }}
                                </div>
                            </div>

                            <!-- 就学援助認定者生年月日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">保護者（就学援助受給者）生年月日</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->guardian_birth_date ? $userApplication->guardian_birth_date->format('Y-m-d') : '' }}
                                </div>
                            </div>

                            <!-- 電話番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">電話番号</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->guardian_phone }}
                                </div>
                            </div>

                            <!-- メールアドレス -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">メールアドレス</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->guardian_email }}
                                </div>
                            </div>

                            <!-- 対象児童名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">対象児童名</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->child_name }}
                                </div>
                            </div>

                            <!-- 対象児童名カナ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">対象児童名カナ</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->child_name_kana ?? '' }}
                                </div>
                            </div>

                            <!-- 対象児童生年月日 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">対象児童生年月日</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->child_birth_date ? $userApplication->child_birth_date->format('Y-m-d') : '' }}
                                </div>
                            </div>

                            <!-- 小学校名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">学校名</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->elementary_school_name }}
                                </div>
                            </div>

                            <!-- 学年 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">学年</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->grade }}
                                </div>
                            </div>

                            <!-- 調査同意 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">調査同意</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->survey_consent ? 'はい' : 'いいえ' }}
                                </div>
                            </div>

                            <!-- プライバシーポリシー同意 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">プライバシーポリシー同意</label>
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    {{ $userApplication->privacy_policy_agreed ? '同意済' : '未同意' }}
                                </div>
                            </div>
                        </div>

                        <!-- 住所 -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">住所</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900 whitespace-pre-wrap">
                                {{ $userApplication->guardian_address }}
                            </div>
                        </div>

                        <!-- 対象児童の住所 -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">対象児童の住所</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900 whitespace-pre-wrap">
                                {{ $userApplication->child_address }}
                            </div>
                        </div>

                        <!-- 申請者と同一の住所 -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">申請者と同一の住所</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $userApplication->child_address_same_as_guardian ? 'はい' : 'いいえ' }}
                            </div>
                        </div>

                        <!-- 児童が自治体に住所登録があり、就学援助を受給している場合 -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">児童が自治体に住所登録があり、就学援助を受給している場合</label>
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                {{ $userApplication->child_registered_in_municipality_and_receiving_scholarship ? 'はい' : 'いいえ' }}
                            </div>
                        </div>
                    </div>

                    <!-- 教室情報セクション -->
                    <div class="mb-8">
                        <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">教室情報</h3>
                        
                        @for($i = 1; $i <= 3; $i++)
                            @php
                                $classroomName = "classroom_name_{$i}";
                                $classroomLocation = "classroom_location_{$i}";
                                $classroomPhone = "classroom_phone_{$i}";
                                $classroomContactPerson = "classroom_contact_person_{$i}";
                            @endphp
                            
                            @if($userApplication->$classroomName)
                                <div class="mb-6 p-4 bg-gray-50 rounded-md">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">教室{{ $i }}</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">教室名</label>
                                            <div class="text-sm text-gray-900">{{ $userApplication->$classroomName }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">所在地</label>
                                            <div class="text-sm text-gray-900">{{ $userApplication->$classroomLocation }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">電話番号</label>
                                            <div class="text-sm text-gray-900">{{ $userApplication->$classroomPhone }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">担当者</label>
                                            <div class="text-sm text-gray-900">{{ $userApplication->$classroomContactPerson }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    </div>

                    <!-- 添付ファイルセクション -->
                    <div class="mb-6">
                        <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">添付ファイル</h3>
                        @if($userApplication->hasDocument())
                            <div class="flex items-center space-x-4">
                                <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-900">
                                    <i class="fas fa-file mr-2"></i>{{ $userApplication->document_original_filename }}
                                </div>
                                <a href="{{ route('admin.user-applications.document.download', $userApplication) }}" 
                                   class="btn-base btn-update btn-m">
                                    <i class="fas fa-download mr-2"></i>ダウンロード
                                </a>
                            </div>
                        @else
                            <div class="px-4 py-2 bg-gray-50 rounded-md text-sm text-gray-500">
                                添付ファイルはありません
                            </div>
                        @endif
                    </div>

                    <!-- ダウンロード対象外・備考（編集可能） -->
                    <div class="mb-6">
                        <h3 class="text-md font-semibold text-gray-900 mb-4 border-b border-gray-200 pb-2">運営メモ</h3>
                        <form action="{{ route('admin.user-applications.update', $userApplication) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           name="is_excluded_from_download"
                                           id="is_excluded_from_download"
                                           value="1"
                                           {{ old('is_excluded_from_download', $userApplication->is_excluded_from_download) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="is_excluded_from_download" class="ml-2 block text-sm font-medium text-gray-700">
                                        ダウンロード対象外
                                    </label>
                                </div>
                                <div>
                                    <label for="admin_remarks" class="block text-sm font-medium text-gray-700 mb-2">備考</label>
                                    <textarea name="admin_remarks"
                                              id="admin_remarks"
                                              rows="4"
                                              class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('admin_remarks', $userApplication->admin_remarks) }}</textarea>
                                </div>
                                <div>
                                    <button type="submit" class="btn-base btn-update btn-m">
                                        <i class="fas fa-save mr-2"></i>保存
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- 出力状態 -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">出力状態</label>
                        <div class="px-4 py-2 bg-gray-50 rounded-md text-sm">
                            @if($userApplication->is_excluded_from_download)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    対象外
                                </span>
                            @elseif($userApplication->is_exported)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    出力済み
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    未出力
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="mt-6 flex justify-end space-x-4">
                <a href="{{ route('admin.user-applications.index') }}" 
                   class="btn-base btn-secondary btn-m">
                    <i class="fas fa-arrow-left mr-2"></i>一覧に戻る
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


