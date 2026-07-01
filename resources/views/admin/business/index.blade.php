@extends('layouts.app')

@section('title', '事業者管理 - 習い事クーポン管理システム')

@section('content')

<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>事業者管理</span></li>
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
            <form method="GET" action="{{ route('admin.business.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- フリーワード検索 -->
                    <div>
                        <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                            フリーワード検索
                        </label>
                        <input type="text" 
                               id="keyword" 
                               name="keyword" 
                               value="{{ old('keyword', $filters['keyword'] ?? '') }}"
                               placeholder="事業者名・代表者名・メールアドレスなど"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <!-- ステータス -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            ステータス
                        </label>
                        <select id="status" 
                                name="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">すべて</option>
                            @foreach(\App\Models\BusinessInfo::getAvailableStatuses() as $status)
                                <option value="{{ $status }}" {{ old('status', $filters['status'] ?? '') == $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-4 mt-4">
                    <a href="{{ route('admin.business.index') }}" class="btn-base btn-back btn-m">
                        <i class="fas fa-redo mr-2"></i>リセット
                    </a>
                    <button type="submit" class="btn-base btn-search btn-m">
                        <i class="fas fa-search mr-2"></i>検索
                    </button>
                    <a href="{{ route('admin.business.export-csv', request()->query()) }}" class="btn-base btn-m btn-export">
                        <i class="fas fa-file-csv mr-2"></i>CSV出力
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 事業者一覧 -->
    <div class="bg-white shadow rounded-lg">
		<div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-building text-gray-400 mr-2"></i>
                事業者一覧
            </h2>
			<a href="{{ route('admin.business.create') }}" 
               class="btn-base btn-create btn-m">
                <i class="fas fa-plus mr-2"></i>新規事業者登録
            </a>
        </div>
        <div class="p-6">
            <div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">事業者名</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">代表者名</th>
                            <!-- th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メールアドレス</th -->
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室名</th>
                            <!-- th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">教室数</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">コース数</th -->
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" width="90px">ステータス</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" width="90px">状態</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" width="90px">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($businesses as $business)
                            <tr class="{{ !$business->is_active ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $business->id }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $business->business_name }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">{{ $business->representative_name }}</td>
                                <!-- td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $business->email }}</td -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $business->classrooms->first()?->classroom_name ?? '—' }}
                                </td>
                                <!-- td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $business->classrooms->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {{ $business->classrooms->sum(fn($c) => $c->courses->count()) }}
                                    </span>
                                </td -->
								<td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $status = $business->status ?? '未着手';
                                        $statusColor = $business->getStatusColor();
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                        {{ $status }}
                                    </span>
								</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($business->is_active)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            有効
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            無効
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.business.edit', $business) }}" 
                                           class="btn-base btn-update btn-xs">
                                            <i class="fas fa-edit mr-1"></i>編集
                                        </a>
                                        
										<!--
                                        @if($business->is_active)
                                            <form action="{{ route('admin.business.deactivate', $business) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn-base btn-disable btn-xs" 
                                                        onclick="return confirm('この事業者を無効化しますか？関連する教室、コース、ユーザーアカウントも無効化されます。')">
                                                    <i class="fas fa-times mr-1"></i>無効化
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.business.activate', $business) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn-base btn-create btn-xs">
                                                    <i class="fas fa-check mr-1"></i>有効化
                                                </button>
                                            </form>
                                        @endif
										-->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-building text-4xl text-gray-300 mb-4"></i>
                                        <p>登録されている事業者がありません。</span>
                                        <a href="{{ route('admin.business.create') }}" 
                                           class="mt-4 inline-block btn-base btn-create btn-m">
                                            <i class="fas fa-plus mr-2"></i>最初の事業者を登録
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($businesses->hasPages())
                <div class="mt-6 flex justify-center" id="pagination">
                    {{ $businesses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection