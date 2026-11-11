@extends('layouts.app')

@section('title', $classroom->classroom_name . ' - 利用者マイページ')

@section('content')
<div class="min-h-screen bg-green-100">

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
		<ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><a href="{{ route('user.course.search') }}" class="hover:text-gray-700">習い事検索</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>{{ $classroom->classroom_name }}</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <!-- 教室情報 -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">{{ $classroom->classroom_name }}</h2>
                        
                        @if($classroom->hasClassroomImage())
                            <div class="text-center mb-6">
                                <img src="{{ $classroom->getPublicClassroomImageDownloadUrl('medium') }}" 
                                     alt="{{ $classroom->classroom_name }}" 
                                     class="w-100% mx-auto rounded-lg shadow-md">
                            </div>
                        @endif
                        
                        <dl class="space-y-4">
                            @if($classroom->classroom_introduction)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">紹介文</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $classroom->classroom_introduction }}</dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">住所</dt>
                                <dd class="text-sm text-gray-900">
                                    〒{{ $classroom->classroom_postal_code }}<br>
                                    {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}{{ $classroom->classroom_building_name ?? '' }}
                                </dd>
                            </div>

                            @if($classroom->classroom_phone)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">電話番号</dt>
                                    <dd class="text-sm text-gray-900">{{ $classroom->classroom_phone }}</dd>
                                </div>
                            @endif

                            @if($classroom->business_hours)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">営業時間</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $classroom->business_hours }}</dd>
                                </div>
                            @endif

                            @if($classroom->holiday)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">定休日</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $classroom->holiday }}</dd>
                                </div>
                            @endif

							@if($business->website_url)
								<div>
									<dt class="text-sm font-medium text-gray-500 mb-1">ウェブサイト</dt>
									<dd class="text-sm text-gray-900"><a href="{{ $business->website_url }}" target="_blank">{{ $business->website_url }}</a></dd>
								</div>
							@endif

                            @if($classroom->lessonCategoryInfo)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">習い事の種別</dt>
                                    <dd class="text-sm text-gray-900">{{ $classroom->lessonCategoryInfo->name }}</dd>
                                </div>
                            @endif

                            @if($qrOnly)
                                <div>
                                    <dd class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-qrcode mr-1"></i>現地QR決済のみ
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
					@if($classroom->allowsAmountSpecifiedUsage())
					<div class="text-center mb-4">
						@if($qrOnly && !$qrAccess)
							<button type="button" class="btn-base btn-disable btn-s" disabled title="この教室はQRコードからのアクセスが必要です">金額指定利用</button>
						@else
							<a href="{{ route('user.course.application', ['classroom' => $classroom->id, 'course' => -1]) }}" class="btn-base btn-create btn-s">金額指定利用</a>
						@endif
					</div>
					@endif

                    <!-- コース一覧 -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">コース一覧</h3>
                        @if($courses->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($courses as $course)
                                    <div class="course-card bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer overflow-hidden"
                                        data-course-name="{{ e($course->course_name) }}"
                                        data-course-price="{{ number_format($course->price) }}"
                                        data-course-description="{{ e($course->course_description ?? '') }}">
                                        <div class="p-4 flex flex-col h-full">
                                            <h4 class="text-base font-semibold text-gray-900 mb-2 line-clamp-2">{{ $course->course_name }}</h4>
                                            <dl class="space-y-1 text-sm flex-grow">
                                                <div>
                                                    <dt class="text-gray-500">対象学年</dt>
                                                    <dd class="text-gray-900">
                                                        @if($course->grades)
                                                            {{ implode(', ', $course->grades) }}
                                                        @else
                                                            <span class="text-gray-400">未設定</span>
                                                        @endif
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="text-gray-500">料金</dt>
                                                    <dd class="text-gray-900 font-medium">
													@if($course->tax_type == 'inclusive')
														<span class="text-gray-500">¥{{ number_format($course->price) }}</span>
													@elseif($course->tax_type == 'exclusive')
														<span class="text-gray-500">¥{{ number_format($course->price * 1.1) }}</span></span>
													@endif
                                                    </dd>
                                                </div>
                                            </dl>
                                            <div class="mt-4 flex flex-wrap gap-2" onclick="event.stopPropagation()">
                                                @if($qrOnly && !$qrAccess)
                                                    <button type="button" class="btn-base btn-disable btn-s" disabled title="この教室はQRコードからのアクセスが必要です">申し込み</button>
                                                @else
                                                    <a href="{{ route('user.course.application', ['classroom' => $classroom->id, 'course' => $course->id]) }}" class="btn-base btn-create btn-s">申し込み</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <p class="text-sm text-yellow-800">コース未設定</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('course-modal');
    const modalCourseName = document.getElementById('modal-course-name');
    const modalCoursePrice = document.getElementById('modal-course-price');
    const modalCourseDescription = document.getElementById('modal-course-description');

    function showModal(card) {
        const courseName = card.getAttribute('data-course-name');
        const coursePrice = card.getAttribute('data-course-price');
        const courseDescription = card.getAttribute('data-course-description');

        modalCourseName.textContent = courseName;
        modalCoursePrice.textContent = '¥' + coursePrice;

        if (courseDescription) {
            modalCourseDescription.textContent = courseDescription;
        } else {
            modalCourseDescription.innerHTML = '<span class="text-gray-400">説明なし</span>';
        }

        modal.classList.remove('hidden');
    }

    document.querySelectorAll('.course-card').forEach(function(card) {
        card.addEventListener('click', function() {
            showModal(this);
        });
    });

    document.querySelectorAll('.course-card .open-modal-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const card = this.closest('.course-card');
            if (card) showModal(card);
        });
    });
});

function closeModal() {
    const modal = document.getElementById('course-modal');
    modal.classList.add('hidden');
}
</script>
@endpush
