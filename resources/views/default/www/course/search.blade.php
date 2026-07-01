@extends('default.www.layout')

@section('title', '習い事検索 ｜ 習い事クーポン管理システム')
@section('body_id', 'other')

@section('content')
<style>
.uk-container a {
    text-decoration: none;
}
</style>
<div class="bg_content_wrap">
    <div class="bg_content_inner">
        <section id="sec_article">
		<!-- breadcrumb -->
		<ul class="uk-breadcrumb">
						<li><a href="/">TOP</a></li>
						<li><span>習い事検索</span></li>
					</ul>
            <div class="uk-container uk-margin-large">
				<h2 class="uk-h2 uk-text-center">
				<span class="ttl anime" style="opacity: 1; transform: translate(0px, 0px);">習い事検索</span>
				</h2>
				<div class="content_wrap uk-clearfix anime" uk-grid>
					<div class="content_inner uk-flex-wrap-stretch">
						<div class="illust_01 other_news"><img src="{{ asset('subdomain_assets/www/images/img_about_01.png') }}" width="165" alt=""></div>
                        ※　クーポンが利用できる習い事教室の情報は、順次掲載していきます
                <!-- タブ -->
                <ul class="uk-tab" uk-tab>
                    <li class="{{ $tab === 'condition' ? 'uk-active' : '' }} uk-tab-search uk-width-1-2">
                        <a href="#condition-tab">条件から検索</a>
                    </li>
                    <li class="{{ $tab === 'map' ? 'uk-active' : '' }} uk-tab-search uk-width-1-2">
                        <a href="#map-tab">地図から検索</a>
                    </li>
                </ul>

                <!-- タブコンテンツ -->
                <ul class="uk-switcher uk-margin">
                    <!-- 条件検索タブ -->
                    <li id="condition-tab" class="{{ $tab === 'condition' ? 'uk-active' : '' }}">
                        <div class="uk-margin-top">
                    <!-- 検索フォーム -->
                    <form method="GET" action="{{ route('course.search') }}" class="uk-form-stacked">
                        <input type="hidden" name="tab" value="condition">
                        
                        <div class="uk-grid-small" uk-grid>
                            <!-- フリーワード検索 -->
                            <div class="uk-width-1-1 uk-width-1-1@m">
                                <label class="uk-form-label uk-text-large uk-text-bold" for="keyword">フリーワード</label>
                                <div class="uk-form-controls">
                                    <input class="uk-input" type="text" id="keyword" name="keyword" value="{{ $keyword }}" placeholder="教室名・教室紹介・コース名・コース詳細から検索">
                                </div>
                            </div>

                            <!-- 習い事の種別 -->
                            <div class="uk-width-1-1">
                                <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top">習い事の種別</label>
                                <div class="uk-form-controls">
                                    @if($categories && $categories->count() > 0)
                                        <div class="uk-border-rounded" style="border: 1px solid #e5e7eb; overflow: hidden;">
                                            <table class="uk-table uk-table-divider uk-table-small" style="margin: 0;">
                                                <tbody>
                                                    @foreach($categories as $categoryGroup)
                                                        @if($categoryGroup['children']->count() > 0)
                                                            <tr>
                                                                <td class="uk-width-1-4" style="vertical-align: top; padding: 8px; border-right: 1px solid #e5e7eb; font-weight: 500;">
                                                                    {{ $categoryGroup['parent']->name }}
                                                                </td>
                                                                <td style="padding: 8px;">
                                                                    <div class="uk-grid-small uk-child-width-1-3@m uk-child-width-1-2@s" uk-grid>
                                                                        @foreach($categoryGroup['children'] as $child)
                                                                            <div>
                                                                                <label class="uk-flex uk-flex-middle" style="cursor: pointer; padding: 4px 8px; border-radius: 4px;">
                                                                                    <input type="checkbox" 
                                                                                           name="lesson_category[]" 
                                                                                           value="{{ $child->id }}"
                                                                                           class="uk-checkbox"
                                                                                           {{ is_array($lessonCategory) && in_array($child->id, $lessonCategory) ? 'checked' : '' }}>
                                                                                    <span style="margin-left: 8px; font-size: 14px;">{{ $child->name }}</span>
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="uk-text-muted">習い事種別が登録されていません。</p>
                                    @endif
                                </div>
                            </div>

                            <!-- 対象学年 -->
                            @if(count($grades) > 0)
                            <div class="uk-width-1-1">
                                <label class="uk-form-label uk-text-large uk-text-bold uk-margin-top" for="grade">対象学年</label>
                                <div class="uk-form-controls">
                                    <select id="grade" 
                                            name="grade" 
                                            class="uk-select">
                                        <option value="">すべて</option>
                                        @foreach($grades as $g)
                                            <option value="{{ $g }}" {{ old('grade', $grade ?? '') == $g ? 'selected' : '' }}>
                                                {{ $g }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="uk-margin-top uk-text-center">
                            <button type="submit">検索</button>
                            <a href="{{ route('course.search', ['tab' => 'condition']) }}">リセット</a>
                        </div>
                    </form>

                    <!-- 検索結果 -->
                    <div class="uk-margin-large-top">
                        @if($classrooms->count() > 0)
                            <p class="uk-text-muted">検索結果：{{ $classrooms->total() }}件</p>
                            
                            <div class="uk-grid-small uk-child-width-1-1 uk-child-width-1-2@m" uk-grid>
                                @foreach($classrooms as $classroom)
                                    <div class="uk-card uk-card-default uk-card-body">
                                        <div class="uk-grid-small" uk-grid>
                                            @if($classroom->hasClassroomImage())
                                            <div class="uk-width-1-3">
                                                <img src="{{ $classroom->getPublicClassroomImageDownloadUrl('medium') }}@if($classroom->updated_at != null)?t={{$classroom->updated_at->timestamp}}@endif" 
                                                     alt="{{ $classroom->classroom_name }}" 
                                                     class="uk-width-1-1">
                                            </div>
                                            @endif
                                            <div class="{{ $classroom->hasClassroomImage() ? 'uk-width-2-3' : 'uk-width-1-1' }}">
                                                <h3 class="uk-card-title uk-margin-remove-bottom">
                                                    <a href="{{ route('course.show', $classroom->id) }}" class="uk-link-heading">
                                                        {{ $classroom->classroom_name }}
                                                    </a>
                                                </h3>
                                                <p class="uk-text-small uk-margin-remove-top">
                                                    @if($classroom->lessonCategoryInfo)
                                                        {{ $classroom->lessonCategoryInfo->name }}
                                                    @endif
                                                </p>
                                                <p class="uk-text-small uk-margin-small">
                                                    〒{{ $classroom->classroom_postal_code }}<br>
                                                    {{ $classroom->classroom_prefecture }}{{ $classroom->classroom_city }}{{ $classroom->classroom_address1 }}{{ $classroom->classroom_building_name ?? '' }}<br>
                                                    @if($classroom->classroom_phone)
                                                        TEL: {{ $classroom->classroom_phone }}<br>
                                                    @endif
                                                    @if($classroom->business_hours)
                                                        <span class="uk-text-bold">営業時間</span><br>
                                                        {!! nl2br(e($classroom->business_hours)) !!}<br>
                                                    @endif
                                                    @if($classroom->holiday)
                                                        <span class="uk-text-bold">定休日</span><br>
                                                        {!! nl2br(e($classroom->holiday)) !!}
                                                    @endif
                                                </p>
                                                <a href="{{ route('course.show', $classroom->id) }}" class="uk-button uk-button-text">
                                                    詳細を見る <span uk-icon="arrow-right"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- ページネーション -->
                            @if($classrooms->hasPages())
                                <div class="uk-margin-top" style="height: 50px;">
                                    {{ $classrooms->links('vendor.pagination.uikit') }}
                                </div>
                            @endif
                        @else
                            <div class="uk-alert-warning" uk-alert>
                                <p>検索条件に一致する教室が見つかりませんでした。</p>
                            </div>
                        @endif
                    </div>
                    </div>
                    </li>

                    <!-- 地図検索タブ -->
                    <li id="map-tab" class="{{ $tab === 'map' ? 'uk-active' : '' }}">
                        <div class="uk-margin-top">
                            <div id="map" style="height: 600px; width: 100%;"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
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
    popupContent += '<a href="' + classroom.url + '" style="display: inline-block; margin-top: 8px; padding: 4px 12px; background: #1e87f0; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">詳細</a>';
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
                console.log('Adding marker at:', classroom.latitude, classroom.longitude);
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
    map = L.map('map',{gestureHandling: true}).setView([{{ $subdomain->latitude ?? 34.7818 }}, {{ $subdomain->longitude ?? 135.4200 }}], 5);

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
    console.log('DOM loaded, tab:', '{{ $tab }}');
    
    // 初期表示が地図タブの場合
    @if($tab === 'map')
    console.log('Initial tab is map, initializing immediately');
    setTimeout(function() {
        initMap();
    }, 100);
    @endif

    // UIkitのタブ切り替えイベントを監視
    const tabElement = document.querySelector('[uk-tab]');
    if (tabElement) {
        console.log('Setting up tab event listener');
        
        // UIkit 3のイベントリスナー（switcherのshowイベント）
        UIkit.util.on(document, 'show', '[uk-switcher]', function(e) {
            console.log('Switcher show event:', e);
            const target = e.target;
            if (target && target.id === 'map-tab') {
                console.log('Map tab shown, initializing map');
                // 少し遅延させてから初期化（タブの表示アニメーションを待つ）
                setTimeout(function() {
                    initMap();
                }, 300);
            }
        });
        
        // フォールバック：タブリンクのクリックイベントも監視
        const mapTabLink = document.querySelector('[uk-tab] a[href="#map-tab"]');
        if (mapTabLink) {
            mapTabLink.addEventListener('click', function() {
                console.log('Map tab clicked');
                setTimeout(function() {
                    initMap();
                }, 500);
            });
        }
        
        // MutationObserverでタブの表示を監視
        const mapTab = document.getElementById('map-tab');
        if (mapTab) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const isActive = mapTab.classList.contains('uk-active');
                        if (isActive && !mapInitialized) {
                            console.log('Map tab became active (via observer)');
                            setTimeout(function() {
                                initMap();
                            }, 300);
                        }
                    }
                });
            });
            
            observer.observe(mapTab, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    } else {
        console.error('Tab element not found');
    }
});
</script>
@endsection
