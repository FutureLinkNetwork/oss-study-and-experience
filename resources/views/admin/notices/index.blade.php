@extends('layouts.app')

@section('title', 'お知らせ管理')

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>お知らせ管理</span></li>
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

    <!-- 検索フィルター -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 mt-4">
        <form method="GET" action="{{ route('admin.notices.index') }}" class="space-y-4">
			<div class="form-row cols-4">
				<div class="field-group">
					<label for="search" class="field-label">検索キーワード</label>
					<input type="text" name="search" value="{{ request('search') }}". placeholder="タイトル・内容で検索" class="field-base field-normal w-full">
				</div>
				<div class="field-group">
					<label class="field-label">&nbsp;</label>
					<button type="submit" class="btn-base btn-search btn-m w-full">
					<i class="fas fa-search mr-2"></i>検索
					</button>
				</div>
			</div>
        </form>
    </div>

    <!-- お知らせ一覧 -->
    <div class="bg-white shadow rounded-lg">
		<div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-building text-gray-400 mr-2"></i>
                お知らせ一覧
            </h2>
			<a href="{{ route('admin.notices.create') }}" class="btn-base btn-create btn-m">
    	        <i class="fas fa-plus mr-2"></i>新規作成
	        </a>

        </div>
	    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if($notices->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-widxer w-full">
                                タイトル
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                お知らせ日付
                            </th>
                            @if(Auth::user()->level >= 100)
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                サブドメイン
                            </th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                公開期間
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                表示設定
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                作成者
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($notices as $notice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ Str::limit($notice->title, 40) }}
                                        </div>
                                        @if($notice->hasLocation())
                                            <div class="text-xs text-blue-600 mt-1">
                                                <i class="fas fa-map-marker-alt mr-1"></i>位置情報あり
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $notice->notice_date->format('Y/m/d') }}
                            </td>
                            @if(Auth::user()->level >= 100)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $notice->subdomain->name ?? '-' }}
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $notice->publish_period_display }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-col gap-1">
                                    @if($notice->show_on_public)
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">LP</span>
                                    @endif
                                    @if($notice->show_on_user_dashboard)
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">ユーザー</span>
                                    @endif
                                    @if($notice->show_on_business_dashboard)
                                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">事業者</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $notice->creator->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.notices.edit', $notice) }}" class="btn-base btn-update btn-xs">
                                        <i class="fas fa-edit mr-1"></i>編集
                                    </a>
                                    <form method="POST" action="{{ route('admin.notices.destroy', $notice) }}" 
                                          class="inline-block" onsubmit="return confirm('本当に削除しますか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-base btn-disable btn-xs">
                                            <i class="fas fa-trash mr-1"></i>削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- ページネーション -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notices->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-bullhorn text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 text-lg mb-4">お知らせが見つかりませんでした</p>
                <a href="{{ route('admin.notices.create') }}" class="btn-base btn-create btn-m">
                    <i class="fas fa-plus mr-2"></i>最初のお知らせを作成
                </a>
            </div>
        @endif
            </div>
		</div>
		</div>
	</div>
@endsection