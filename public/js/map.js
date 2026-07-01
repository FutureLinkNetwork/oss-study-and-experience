/**
 * 地図表示共通ライブラリ
 * Leaflet.jsを使用した地図表示機能
 */
(function() {
    'use strict';

    /**
     * 地図を初期化
     * @param {Object} options - 初期化オプション
     * @param {string} options.containerId - 地図コンテナのID（デフォルト: 'map'）
     * @param {number} options.latitude - 緯度（必須）
     * @param {number} options.longitude - 経度（必須）
     * @param {number} options.zoom - ズームレベル（デフォルト: 17）
     * @param {boolean} options.showMarker - マーカーを表示するか（デフォルト: true）
     * @param {string} options.popupContent - ポップアップに表示する内容（オプション）
     * @param {boolean} options.enableClick - クリックイベントを有効化するか（デフォルト: false）
     * @param {Object} options.onClick - クリック時のコールバック関数（オプション）
     * @returns {Object|null} Leaflet mapオブジェクト、またはnull（エラー時）
     */
    window.initMap = function(options) {
        // 必須パラメータのチェック
        if (typeof options === 'undefined' || options === null) {
            console.error('Map initialization options are required');
            return null;
        }

        if (typeof options.latitude === 'undefined' || typeof options.longitude === 'undefined') {
            console.error('Latitude and longitude are required');
            return null;
        }

        // デフォルト値の設定
        const containerId = options.containerId || 'map';
        const latitude = parseFloat(options.latitude);
        const longitude = parseFloat(options.longitude);
        const zoom = options.zoom || 17;
        const showMarker = options.showMarker !== false; // デフォルト: true
        const enableClick = options.enableClick === true; // デフォルト: false
        const popupContent = options.popupContent || null;
        const onClick = options.onClick || null;
        const gestureHandling = options.gestureHandling;

        // コンテナの存在確認
        const mapContainer = document.getElementById(containerId);
        if (!mapContainer) {
            console.error('Map container not found: #' + containerId);
            return null;
        }
        
        // 地図を初期化（gestureHandling を L.map の options に渡す）
        let map = null;
        try {
            const mapOptions = {};
            if (typeof gestureHandling !== 'undefined') {
                mapOptions.gestureHandling = gestureHandling;
            }

            map = L.map(containerId, mapOptions).setView([latitude, longitude], zoom);
        } catch (e) {
            console.error('L.map failed', e);
            throw e;
        }

        // OpenStreetMapレイヤーを追加
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // マーカーを表示
        let marker = null;
        if (showMarker) {
            marker = L.marker([latitude, longitude]).addTo(map);
            
            // ポップアップを設定
            if (popupContent) {
                marker.bindPopup(popupContent);
                // 詳細ページの場合は自動でポップアップを開く
                if (!enableClick) {
                    marker.openPopup();
                }
            }
        }

        // クリックイベントを有効化（編集モード用）
        if (enableClick) {
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                // 既存のマーカーを削除
                if (marker) {
                    map.removeLayer(marker);
                }
                
                // 新しいマーカーを作成
                marker = L.marker([lat, lng]).addTo(map);
                
                // ポップアップを設定
                if (popupContent) {
                    marker.bindPopup(popupContent);
                }
                
                // コールバック関数を実行
                if (typeof onClick === 'function') {
                    onClick(lat, lng, marker, map);
                }
            });
        }

        return map;
    };

    /**
     * フォームフィールドに座標を設定するヘルパー関数
     * @param {number} lat - 緯度
     * @param {number} lng - 経度
     */
    window.setMapCoordinates = function(lat, lng) {
        const latField = document.querySelector("input[name='latitude']");
        const lngField = document.querySelector("input[name='longitude']");
        
        if (latField) {
            latField.value = lat.toFixed(8);
        }
        if (lngField) {
            lngField.value = lng.toFixed(8);
        }
    };
})();

