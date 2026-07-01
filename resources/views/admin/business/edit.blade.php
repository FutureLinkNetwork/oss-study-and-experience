@extends('layouts.app')

@section('title', '事業者編集 - 習い事クーポン管理システム')

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
                <li><span>事業者編集</span></li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

    <!-- 事業者情報編集フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-edit text-gray-400 mr-2"></i>
                事業者情報編集
            </h2>
        </div>
        
        @include('admin.business.form', [
            'action' => route('admin.business.update', $business),
            'method' => 'PUT',
            'business' => $business,
            'formToken' => $formToken ?? session('form_access_token')
        ])
    </div>

    <!-- 教室一覧セクション -->
    <div class="mt-8 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-school text-gray-400 mr-2"></i>
                教室一覧
            </h2>
            <a href="{{ route('admin.business.create-classroom', $business) }}" 
               class="btn-base btn-create btn-s">
                <i class="fas fa-plus mr-2"></i>新規教室登録
            </a>
        </div>
        <div class="p-6">
            @if($business->classrooms->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室名</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">所在地</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">担当者</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">連絡先</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">コース数</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">申請状況</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状態</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($business->classrooms as $classroom)
                                <tr class="{{ !$classroom->is_active ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $classroom->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $classroom->classroom_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $classroom->classroom_address }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $classroom->contact_person }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $classroom->contact_phone }}</div>
                                        <div class="text-sm text-gray-500">{{ $classroom->contact_email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="label-base label-info label-xs">
                                            {{ $classroom->courses->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($classroom->apply == 1)
                                            <span class="label-base label-active label-xs">
                                                承認済
                                            </span>
                                        @else
                                            <span class="label-base label-warning label-xs">
                                                申請中
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($classroom->is_active)
                                            <span class="label-base label-active label-xs">
                                                有効
                                            </span>
                                        @else
                                            <span class="label-base label-inactive label-xs">
                                                無効
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.business.edit-classroom', [$business, $classroom]) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-edit mr-1"></i>編集
                                            </a>
                                            
                                            @if($classroom->is_active)
                                                <form action="{{ route('admin.business.deactivate-classroom', [$business, $classroom]) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn-base btn-disable btn-xs" 
                                                            onclick="return confirm('この教室を無効化しますか？関連するコースも無効化されます。')">
                                                        <i class="fas fa-times mr-1"></i>無効化
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.business.activate-classroom', [$business, $classroom]) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn-base btn-create btn-xs">
                                                        <i class="fas fa-check mr-1"></i>有効化
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-school text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">登録されている教室がありません。</span>
                    <a href="{{ route('admin.business.create-classroom', $business) }}" 
                       class="btn-base btn-create btn-m">
                        <i class="fas fa-plus mr-2"></i>最初の教室を登録
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection