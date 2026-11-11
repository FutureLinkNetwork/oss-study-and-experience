@extends('layouts.app')

@section('title', '利用者マイページ - '.$subdomain->system_name)

@section('content')
<div class="min-h-screen bg-green-100">
    <!-- メインコンテンツ -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- ウェルカムメッセージ -->
        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
						<h2 class="text-lg font-medium mb-4 text-gray-900">お知らせ</h2>
						<ul id="notices-list" class="list-none">
						@foreach($notices as $notice)
							<li class="mb-4">
								<a href="{{ route('user.notices.show', $notice->id) }}" class="text-blue-500 hover:text-blue-700">
									<span class="text-gray-500 ml-6">{{ $notice->notice_date_display }}</span>
									<span class="text-black ml-2">{{ $notice->title }}</span>	
								</a>
							</li>
						@endforeach
					</ul>
					@if($notices->hasPages())
						<div class="text-center mt-6" id="pagination">
							{{ $notices->links() }}
						</div>
					@endif
				</div>
            </div>
        </div>

        <!-- 機能カード -->
        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- コース検索 -->
                <a href="{{ route('user.course.search') }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-search text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">習い事検索</h4>
                                <p class="text-sm text-gray-500">習い事を検索してクーポンを利用する／習い事が見つからないときはリクエスト</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm text-blue-600 font-medium">検索する <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                    </div>
                </a>

                <!-- 申込管理 -->
                <a href="{{ route('user.applications.index') }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clipboard-check text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">クーポン利用履歴</h4>
                                <p class="text-sm text-gray-500">履歴から繰り返しクーポンを利用できます</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm text-blue-600 font-medium">確認する <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                    </div>
                </a>

                <!-- 問い合わせ -->
                <a href="{{ route('user.inquiries.index') }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-envelope text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">問い合わせ</h4>
                                <p class="text-sm text-gray-500">運営への問い合わせの送信・履歴確認</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-sm text-blue-600 font-medium">問い合わせする <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection