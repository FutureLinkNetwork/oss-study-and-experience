@extends('layouts.app')

@section('title', '教室管理 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>教室管理</span></li>
            </ol>
        </nav>
	<!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

            <!-- 成功メッセージ -->
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

            <!-- エラーメッセージ -->
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

            <!-- 教室一覧 -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">教室一覧</h3>
                        <span class="text-sm text-gray-500">{{ $classrooms->total() }}件の教室</span>
                    </div>
                </div>

                @if($classrooms->count() > 0)
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        教室名
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        代表者
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        住所
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        状態
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($classrooms as $classroom)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $classroom->classroom_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $classroom->classroom_name_kana }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $classroom->classroom_representative_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $classroom->classroom_representative_name_kana }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                〒{{ $classroom->classroom_postal_code }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}
                                                @if($classroom->classroom_building_name)
                                                    <br>{{ $classroom->classroom_building_name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($classroom->is_active)
                                                <span class="label-base label-active label-xs">
                                                    <i class="fas fa-check-circle mr-1"></i>有効
                                                </span>
                                            @else
                                                <span class="label-base label-inactive label-xs">
                                                    <i class="fas fa-times-circle mr-1"></i>無効
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <a href="{{ route('business.classrooms.show', $classroom) }}" 
                                               class="btn-base btn-update btn-xs">
                                                <i class="fas fa-eye mr-1"></i>詳細・編集
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ページネーション -->
                    @if($classrooms->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $classrooms->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-school text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">教室が登録されていません</h3>
                        <p class="text-gray-500">
                            教室情報は管理者により登録されます。<br>
                            教室の登録についてはシステム管理者にお問い合わせください。
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection