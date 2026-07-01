@extends('default.www.layout')

@section('title', $classroom->classroom_name . ' ｜ 習い事クーポン管理システム')
@section('body_id', 'other')

@section('content')
<div class="bg_content_wrap">
    <div class="bg_content_inner">
        <section id="sec_article">
		<!-- breadcrumb -->
		<ul class="uk-breadcrumb">
						<li><a href="/">TOP</a></li>
						<li><a href="/course/search">習い事検索</a></li>
						<li><span>{{ $classroom->classroom_name }}</span></li>
					</ul>
            <div class="uk-container uk-margin-large">
				<!-- <h2 class="uk-h2 uk-text-center">
				<span class="ttl">教室情報</span>
                </h2> -->

                <!-- 教室情報 -->
				<div class="content_wrap uk-clearfix anime uk-grid-stack" style="opacity: 1; transform: translate(0px, 0px);">
                    <h3 class="uk-card-title uk-text-center">{{ $classroom->classroom_name }}</h3>
					@if($classroom->hasClassroomImage())
                        <div class="uk-text-center">
                            <img src="{{ $classroom->getPublicClassroomImageDownloadUrl('medium') }}@if($classroom->updated_at != null)?t={{$classroom->updated_at->timestamp}}@endif" 
                                 alt="{{ $classroom->classroom_name }}" 
                                 class="uk-width-1-2@m">
                        </div>
                        @endif
                    
                    <dl class="uk-description-list">
					@if($classroom->classroom_introduction)
                        <dt>紹介文</dt>
                        <dd>{!! nl2br(e($classroom->classroom_introduction)) !!}</dd>
                        @endif


						<dt>住所</dt>
                        <dd>
                            〒{{ $classroom->classroom_postal_code }}<br>
                            {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}{{ $classroom->classroom_building_name ?? '' }}
                        </dd>

						@if($classroom->classroom_phone)
                        <dt>電話番号</dt>
                        <dd>{{ $classroom->classroom_phone }}</dd>
                        @endif

                        @if($classroom->business_hours)
                        <dt>営業時間</dt>
                        <dd>{!! nl2br(e($classroom->business_hours)) !!}</dd>
                        @endif

                        @if($classroom->holiday)
                        <dt>定休日</dt>
                        <dd>{!! nl2br(e($classroom->holiday)) !!}</dd>
                        @endif

						@if($business->website_url)
						<dt>ウェブサイト</dt>
						<dd><a href="{{ $business->website_url }}" target="_blank">{{ $business->website_url }}</a></dd>
						@endif


                        @if($classroom->classroom_latitude && $classroom->classroom_longitude)
                        <dt>地図</dt>
                        <dd>
                            <div id="classroom-map" style="height: 600px; width: 100%; border: 1px solid #ccc;"></div>
                        </dd>
                        @endif

                    </dl>
                    <h3 class="uk-card-title">コース一覧</h3>
                    
                    @if($courses->count() > 0)
                        <div class="uk-overflow-auto">
                            <table class="uk-table uk-table-divider uk-table-hover">
                                <thead>
                                    <tr>
                                        <th>コース名</th>
                                        <th>料金</th>
                                        <th>コース説明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($courses as $course)
                                        <tr class="course-row" 
                                            data-course-name="{{ e($course->course_name) }}"
                                            data-course-price="{{ number_format($course->price) }}"
                                            data-course-description="{{ e($course->course_description ?? '') }}"
                                            data-course-grades="{{ is_array($course->grades) && count($course->grades) > 0 ? json_encode($course->grades) : '' }}"
                                            style="cursor: pointer;">
                                            <td>{{ $course->course_name }}</td>
                                            <td>¥{{ number_format($course->price) }}</td>
                                            <td>
                                                @if($course->course_description)
                                                    {{ Str::limit($course->course_description, 10, '…') }}
                                                @else
                                                    <span class="uk-text-muted">説明なし</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="uk-alert-warning" uk-alert>
                            <p>現在、有効なコースがありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>

<!-- コース詳細モーダル -->
<div id="course-modal" uk-modal>
    <div class="uk-modal-dialog">
        <div class="uk-modal-header">
            <h2 class="uk-modal-title" id="modal-course-name"></h2>
			<button class="uk-modal-close-default" type="button" uk-close></button>
		</div>
        <div class="uk-modal-body">
            <dl class="uk-description-list">
                <dt>料金</dt>
                <dd id="modal-course-price"></dd>
                @if(isset($grades) && is_array($grades) && count($grades) > 0)
                <dt>対象</dt>
                <dd id="modal-course-grades"></dd>
                @endif
                <dt>コース説明</dt>
                <dd id="modal-course-description"></dd>
            </dl>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@if($classroom->classroom_latitude && $classroom->classroom_longitude)
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
<script src="https://unpkg.com/leaflet-gesture-handling"></script>
<!-- 地図表示共通ライブラリ -->
<script src="{{ asset('js/map.js') }}"></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseRows = document.querySelectorAll('.course-row');
    const modal = UIkit.modal('#course-modal');
    const modalCourseName = document.getElementById('modal-course-name');
    const modalCoursePrice = document.getElementById('modal-course-price');
    const modalCourseGrades = document.getElementById('modal-course-grades');
    const modalCourseDescription = document.getElementById('modal-course-description');
    
    courseRows.forEach(function(row) {
        row.addEventListener('click', function() {
            const courseName = this.getAttribute('data-course-name');
            const coursePrice = this.getAttribute('data-course-price');
            const courseDescription = this.getAttribute('data-course-description');
            const courseGradesJson = this.getAttribute('data-course-grades');
            
            modalCourseName.textContent = courseName;
            modalCoursePrice.textContent = '¥' + coursePrice;
            
            // 学年情報を表示
            if (modalCourseGrades) {
                if (courseGradesJson) {
                    try {
                        const grades = JSON.parse(courseGradesJson);
                        if (Array.isArray(grades) && grades.length > 0) {
                            modalCourseGrades.textContent = grades.join('、');
                        } else {
                            modalCourseGrades.textContent = '-';
                        }
                    } catch (e) {
                        modalCourseGrades.textContent = '-';
                    }
                } else {
                    modalCourseGrades.textContent = '-';
                }
            }
            
            if (courseDescription) {
                // 改行を<br>に変換して表示
                modalCourseDescription.innerHTML = courseDescription.replace(/\n/g, '<br>');
            } else {
                modalCourseDescription.innerHTML = '<span class="uk-text-muted">説明なし</span>';
            }
            
            modal.show();
        });
});
    @if($classroom->classroom_latitude && $classroom->classroom_longitude)
    // 地図初期化
    const mapContainer = document.getElementById('classroom-map');
	if (mapContainer) {
        const classroomLat = {{ $classroom->classroom_latitude }};
        const classroomLng = {{ $classroom->classroom_longitude }};
        const classroomName = @json($classroom->classroom_name);
        const map = initMap({
            containerId: 'classroom-map',
            latitude: classroomLat,
            longitude: classroomLng,
            zoom: 17,
            showMarker: true,
            popupContent: '<div style="min-width: 200px;"><h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold;">' + classroomName + '</h4></div>',
            enableClick: false,
			gestureHandling: true
        });
    }
    @endif
});
</script>
@endsection

