@extends('layouts.app')

@section('title', '習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
	


	<!-- お知らせ 表示項目はニュース日付とタイトル、リンクはお知らせ詳細ページへ CSSはTailwind CSSを使用 背景色は白の枠を用意し一覧を表示する テキスト色は黒 -->
	 @if($notices->count() > 0 || !empty($hasUndownloadedPayments))
	<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
		<div class="px-4 sm:px-0">
			<div class="grid grid-cols-1 gap-6 sm:grid-cols-1 lg:grid-cols-1">
				<div class="w-full mx-auto">
					<div class="bg-white rounded-lg p-6 shadow-md">
						<h2 class="text-lg font-medium mb-4 text-gray-900">お知らせ</h2>
						<ul id="notices-list" class="list-none">
						@if(!empty($hasUndownloadedPayments))
							<!-- 支払い管理のご確認（未ダウンロードがある場合のみ表示） -->
							<li class="mb-4">
								<a href="{{ route('business.payments.index') }}" class="text-blue-500 hover:text-blue-700">
									<span class="text-gray-500 ml-6">{{ $undownloadedPaymentsLatestCreatedAt }}</span>
									<span class="text-black ml-2">未確認の支払い明細があります。支払い管理のご確認をお願いします</span>
								</a>
							</li>
						@endif

						@foreach($notices as $notice)
							<li class="mb-4">
								<a href="{{ route('business.notices.show', $notice->id) }}" class="text-blue-500 hover:text-blue-700">
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
		</div>
	</div>
	@endif

    <!-- メインコンテンツ -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- 機能カード -->
        <div class="px-4 sm:px-0">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- 教室管理 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-school text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">教室管理</h4>
                                <p class="text-sm text-gray-500">教室情報の確認・編集</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.classrooms.index') }}" 
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>教室管理へ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- コース管理 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chalkboard-teacher text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">コース管理</h4>
                                <p class="text-sm text-gray-500">習い事コースの登録・編集</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.courses.index') }}" 
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>コース管理へ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- クーポン受付管理 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clipboard-list text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">クーポン受付管理</h4>
                                <p class="text-sm text-gray-500">利用者からのクーポン受付状況の確認</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.applications.index') }}" 
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>クーポン受付管理へ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 問い合わせ -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-envelope text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">問い合わせ</h4>
                                <p class="text-sm text-gray-500">運営への問い合わせの送信・履歴確認</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.inquiries.index') }}"
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>問い合わせへ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- レポート -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-bar text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">レポート</h4>
                                <p class="text-sm text-gray-500">売上・利用状況の確認</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.reports.index') }}"
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>レポートへ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 支払管理 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow relative">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center min-w-0">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-yen-sign text-green-600 text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-lg font-medium text-gray-900">支払管理</h4>
                                    <p class="text-sm text-gray-500">月別支払一覧・支払通知PDFダウンロード</p>
                                </div>
                            </div>
                            @if($hasUndownloadedPayments)
                                <span class="flex-shrink-0 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 text-xs font-semibold text-white bg-red-500 rounded-full" aria-label="未確認の支払い明細あり">{{ $undownloadedPaymentsCount }}</span>
                            @endif
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.payments.index') }}"
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>支払管理へ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 登録内容の確認 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-id-card text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">登録内容の確認</h4>
                                <p class="text-sm text-gray-500">事業者情報・口座情報などの確認</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.profile.registration.confirm') }}"
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>登録内容の確認へ
                            </a>
                        </div>
                    </div>
                </div>

                <!-- メールアドレス・パスワードの変更 -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-cog text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">設定変更・マニュアルダウンロード</h4>
                                <p class="text-sm text-gray-500">メールアドレス・パスワードの変更</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('business.profile.edit') }}" 
                               class="btn-base btn-create btn-m">
                                <i class="fas fa-arrow-right mr-2"></i>設定へ
                            </a>
                        </div>
                    </div>
                </div>				
            </div>
        </div>        
    </div>
	</div>
</div>
@endsection