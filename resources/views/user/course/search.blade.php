@extends('layouts.app')

@section('title', '習い事検索 - 利用者マイページ')

@section('content')
<div class="min-h-screen bg-green-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('user.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
				<li><span class="mx-2">/</span></li>
				<li><span>習い事検索</span></li>
            </ol>
        </nav>		
	
    <!-- メインコンテンツ -->
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">
        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <!-- タブ -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <a href="{{ route('user.course.search', ['tab' => 'condition'] + request()->except('tab')) }}" 
                               class="{{ $tab === 'condition' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                条件から検索
                            </a>
                            <a href="{{ route('user.course.search', ['tab' => 'map'] + request()->except('tab')) }}" 
                               class="{{ $tab === 'map' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                地図から検索
                            </a>
                        </nav>
                    </div>

                    <!-- 条件検索タブ -->
                    @if($tab === 'condition')
                        <!-- 検索フォーム -->
                        <form method="GET" action="{{ route('user.course.search') }}" class="mb-6">
                            <input type="hidden" name="tab" value="condition">
                            
                            <div class="space-y-4">
                                <!-- フリーワード検索 -->
                                <div>
                                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                                        フリーワード
                                    </label>
                                    <input type="text" 
                                           id="keyword" 
                                           name="keyword" 
                                           value="{{ $keyword }}" 
                                           placeholder="教室名・教室紹介・コース名・コース詳細から検索"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- 習い事の種別 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        習い事の種別
                                    </label>
                                    @if($categories && $categories->count() > 0)
                                        <div class="border border-gray-300 rounded-md p-4">
                                            <div class="space-y-4">
                                                @foreach($categories as $categoryGroup)
                                                    @if($categoryGroup['children']->count() > 0)
                                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                            <div class="font-medium text-gray-900">
                                                                {{ $categoryGroup['parent']->name }}
                                                            </div>
                                                            <div class="md:col-span-3">
                                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                                                    @foreach($categoryGroup['children'] as $child)
                                                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                                                            <input type="checkbox" 
                                                                                   name="lesson_category[]" 
                                                                                   value="{{ $child->id }}"
                                                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                                                   {{ is_array($lessonCategory) && in_array($child->id, $lessonCategory) ? 'checked' : '' }}>
                                                                            <span class="text-sm text-gray-700">{{ $child->name }}</span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">習い事種別が登録されていません。</p>
                                    @endif
                                </div>

                                <!-- 対象学年 -->
                                @if(isset($grades) && is_array($grades) && count($grades) > 0)
                                <div>
                                    <label for="grade" class="block text-sm font-medium text-gray-700 mb-2">
                                        対象学年
                                    </label>
                                    <select id="grade" 
                                            name="grade" 
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">すべて</option>
                                        @foreach($grades as $g)
                                            <option value="{{ $g }}" {{ old('grade', $grade ?? '') == $g ? 'selected' : '' }}>
                                                {{ $g }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                            </div>

                            <div class="mt-6 flex flex-col md:flex-row justify-center gap-4">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-search mr-2"></i>検索
                                </button>
                                <a href="{{ route('user.course.search', ['tab' => 'condition']) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm font-medium text-center">
                                    リセット
                                </a>
								<a href="/course/request" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm font-medium text-center">
									習い事リクエスト
								</a>
                            </div>
                        </form>

                        <!-- 検索結果 -->
                        <div class="mt-8">
                            @if($classrooms->count() > 0)
                                <p class="text-sm text-gray-600 mb-4">検索結果：{{ $classrooms->total() }}件</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach($classrooms as $classroom)
                                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                                            <div class="p-6">
                                                <div class="flex flex-col md:flex-row gap-4">
                                                    @if($classroom->hasClassroomImage())
                                                        <div class="flex-shrink-0 w-full md:w-32">
                                                            <img src="{{ $classroom->getPublicClassroomImageDownloadUrl('medium') }}" 
                                                                 alt="{{ $classroom->classroom_name }}" 
                                                                 class="w-full h-48 md:h-32 object-cover object-top rounded">
                                                        </div>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                            <a href="{{ route('user.course.show', $classroom->id) }}" class="text-blue-600 hover:text-blue-800">
                                                                {{ $classroom->classroom_name }}
                                                            </a>
                                                        </h3>
                                                        @if($classroom->lessonCategoryInfo)
                                                            <p class="text-sm text-gray-600 mb-2">
                                                                <i class="fas fa-tag mr-1"></i>{{ $classroom->lessonCategoryInfo->name }}
                                                            </p>
                                                        @endif
                                                        <p class="text-sm text-gray-600 mb-2">
                                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                                            〒{{ $classroom->classroom_postal_code }}<br>
                                                            {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}{{ $classroom->classroom_building_name ?? '' }}
                                                        </p>
                                                        @if($classroom->classroom_phone)
                                                            <p class="text-sm text-gray-600 mb-2">
                                                                <i class="fas fa-phone mr-1"></i>{{ $classroom->classroom_phone }}
                                                            </p>
                                                        @endif
                                                        @if($classroom->business_hours)
                                                            <p class="text-sm text-gray-600 mb-2">
                                                                <span class="font-medium text-gray-700">営業時間</span><br>
                                                                {!! nl2br(e($classroom->business_hours)) !!}
                                                            </p>
                                                        @endif
                                                        @if($classroom->holiday)
                                                            <p class="text-sm text-gray-600 mb-2">
                                                                <span class="font-medium text-gray-700">定休日</span><br>
                                                                {!! nl2br(e($classroom->holiday)) !!}
                                                            </p>
                                                        @endif
                                                        <a href="{{ route('user.course.show', $classroom->id) }}" class="mt-3 inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            詳細を見る <i class="fas fa-arrow-right ml-1"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- ページネーション -->
                                @if($classrooms->hasPages())
                                    <div class="text-center mt-6" id="pagination">
                                        {{ $classrooms->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <p class="text-sm text-yellow-800">検索条件に一致する教室が見つかりませんでした。</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- 地図検索タブ -->
                    @if($tab === 'map')
                        <div class="mt-4">
                            <div id="map" style="height: 600px; width: 100%; border-radius: 0.5rem; overflow: hidden;"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($tab === 'map')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" type="text/css">
<script src="https://unpkg.com/leaflet-gesture-handling"></script>


<script>
let map = null;
let mapInitialized = false;
const classrooms = @json($mapClassrooms ?? []);

function escapeHtmlForMapPopup(str) {
    if (str == null || str === '') {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML.replace(/\n/g, '<br>');
}

function buildMapPopupHtml(classroom) {
    let popupContent = '<div style="min-width: 200px;">';
    popupContent += '<h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold;">' + escapeHtmlForMapPopup(classroom.name) + '</h4>';
    if (classroom.category) {
        popupContent += '<p style="margin: 0 0 8px 0; font-size: 14px; color: #666;">' + escapeHtmlForMapPopup(classroom.category) + '</p>';
    }
    if (classroom.business_hours) {
        popupContent += '<p style="margin: 0 0 6px 0; font-size: 13px;"><strong>営業時間</strong><br>' + escapeHtmlForMapPopup(classroom.business_hours) + '</p>';
    }
    if (classroom.holiday) {
        popupContent += '<p style="margin: 0 0 8px 0; font-size: 13px;"><strong>定休日</strong><br>' + escapeHtmlForMapPopup(classroom.holiday) + '</p>';
    }
    popupContent += '<a href="' + classroom.url + '" style="display: inline-block; margin-top: 8px; padding: 4px 12px; background: #2563eb; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">詳細</a>';
    popupContent += '</div>';
    return popupContent;
}

// 地図を初期化する関数
function initMap() {
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        console.error('Map element not found');
        return;
    }
    
    // 既に地図が初期化されている場合は、マーカーのみ追加
    if (mapInitialized && map) {
        // 既存のマーカーをクリア
        map.eachLayer(function(layer) {
            if (layer instanceof L.Marker) {
                map.removeLayer(layer);
            }
        });
        
        // マーカーを再配置
        classrooms.forEach(function(classroom) {
            if (classroom.latitude && classroom.longitude) {
                const marker = L.marker([classroom.latitude, classroom.longitude]).addTo(map);

                marker.bindPopup(buildMapPopupHtml(classroom));
            }
        });
        
        // マーカーが1つ以上ある場合、地図の表示範囲を調整
        if (classrooms.length > 0) {
            const bounds = classrooms
                .filter(c => c.latitude && c.longitude)
                .map(c => [c.latitude, c.longitude]);
            
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
        
        return;
    }
    
    // 地図の初期化
    const defaultLat = {{ $subdomain->latitude ?? 34.7818 }};
    const defaultLng = {{ $subdomain->longitude ?? 135.4200 }};
    map = L.map('map',{gestureHandling: true}).setView([defaultLat, defaultLng], 5);

    // OpenStreetMapタイルレイヤーを追加
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // マーカーを配置
    classrooms.forEach(function(classroom) {
        if (classroom.latitude && classroom.longitude) {
            const marker = L.marker([classroom.latitude, classroom.longitude]).addTo(map);

            marker.bindPopup(buildMapPopupHtml(classroom));
        }
    });

    // マーカーが1つ以上ある場合、地図の表示範囲を調整
    if (classrooms.length > 0) {
        const bounds = classrooms
            .filter(c => c.latitude && c.longitude)
            .map(c => [c.latitude, c.longitude]);
        
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    mapInitialized = true;
    
    // 地図のサイズを調整
    setTimeout(function() {
        if (map) {
            map.invalidateSize();
        }
    }, 100);
}

document.addEventListener('DOMContentLoaded', function() {
    @if($tab === 'map')
    // 初期表示が地図タブの場合
    setTimeout(function() {
        initMap();
    }, 100);
    @endif
});
</script>
@endif
@endpush
