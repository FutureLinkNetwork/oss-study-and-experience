@extends('layouts.app')

@section('title', '利用者申請管理 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>利用者申請管理</span></li>
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

            @if(session('info'))
                <div class="alert-base alert-info alert-m mb-6">
                    <div class="alert-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="alert-message">
                        {{ session('info') }}
                    </div>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            <!-- 絞り込みフォーム -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-filter text-gray-400 mr-2"></i>
                        絞り込み条件
                    </h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.user-applications.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- 就学援助認定番号 -->
                            <div>
                                <label for="certification_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    就学援助認定番号
                                </label>
                                <input type="text" 
                                       id="certification_number" 
                                       name="certification_number" 
                                       value="{{ old('certification_number', $filters['certification_number'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 対象児童名 -->
                            <div>
                                <label for="child_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    対象児童名
                                </label>
                                <input type="text" 
                                       id="child_name" 
                                       name="child_name" 
                                       value="{{ old('child_name', $filters['child_name'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 申請日（From） -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    申請日（From）
                                </label>
                                <input type="date" 
                                       id="date_from" 
                                       name="date_from" 
                                       value="{{ old('date_from', $filters['date_from'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 申請日（To） -->
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    申請日（To）
                                </label>
                                <input type="date" 
                                       id="date_to" 
                                       name="date_to" 
                                       value="{{ old('date_to', $filters['date_to'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 小学校名 -->
                            <div>
                                <label for="elementary_school_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    小学校名
                                </label>
                                <input type="text" 
                                       id="elementary_school_name" 
                                       name="elementary_school_name" 
                                       value="{{ old('elementary_school_name', $filters['elementary_school_name'] ?? '') }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>

                            <!-- 出力/未出力 -->
                            <div>
                                <label for="is_exported" class="block text-sm font-medium text-gray-700 mb-2">
                                    出力/未出力
                                </label>
                                <select id="is_exported" 
                                        name="is_exported" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">全て</option>
                                    <option value="0" {{ (old('is_exported', $filters['is_exported'] ?? '') === '0') ? 'selected' : '' }}>未出力</option>
                                    <option value="1" {{ (old('is_exported', $filters['is_exported'] ?? '') === '1') ? 'selected' : '' }}>出力済み</option>
                                    <option value="excluded" {{ (old('is_exported', $filters['is_exported'] ?? '') === 'excluded') ? 'selected' : '' }}>対象外</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <a href="{{ route('admin.user-applications.index') }}" 
                               class="btn-base btn-back btn-m">
                                <i class="fas fa-redo mr-2"></i>リセット
                            </a>
                            <button type="submit" class="btn-base btn-update btn-m">
                                <i class="fas fa-search mr-2"></i>検索
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 利用者申請一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list text-gray-400 mr-2"></i>
                        利用者申請一覧
                    </h2>
                    @if(($filters['is_exported'] ?? '') !== 'excluded')
                        <form method="GET" action="{{ route('admin.user-applications.export') }}" class="inline">
                            @if(isset($filters['certification_number']) && $filters['certification_number'])
                                <input type="hidden" name="certification_number" value="{{ $filters['certification_number'] }}">
                            @endif
                            @if(isset($filters['child_name']) && $filters['child_name'])
                                <input type="hidden" name="child_name" value="{{ $filters['child_name'] }}">
                            @endif
                            @if(isset($filters['date_from']) && $filters['date_from'])
                                <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                            @endif
                            @if(isset($filters['date_to']) && $filters['date_to'])
                                <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                            @endif
                            @if(isset($filters['elementary_school_name']) && $filters['elementary_school_name'])
                                <input type="hidden" name="elementary_school_name" value="{{ $filters['elementary_school_name'] }}">
                            @endif
                            @if(isset($filters['is_exported']) && $filters['is_exported'] !== '')
                                <input type="hidden" name="is_exported" value="{{ $filters['is_exported'] }}">
                            @endif
                            <button type="submit" class="btn-base btn-export btn-m">
                                <i class="fas fa-file-csv mr-2"></i>CSV出力
                            </button>
                        </form>
                    @endif
                </div>
                <div class="p-6">
                    <div class="">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:150px">申請日</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:150px">就学援助認定番号</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">対象児童名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">小学校名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:130px">出力/未出力</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($userApplications as $userApplication)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $userApplication->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $userApplication->certification_number }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $userApplication->child_name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $userApplication->elementary_school_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
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
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.user-applications.show', $userApplication) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                                                <p>利用者申請がありません。</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($userApplications->hasPages())
                        <div class="mt-6 flex justify-center" id="pagination">
                            {{ $userApplications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


