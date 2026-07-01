<?php
// manifest.jsonから動的にCSSファイル名を取得
$manifestPath = __DIR__ . '/build/manifest.json';

if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $cssFile = $manifest['resources/css/app.css']['file'] ?? 'assets/app.css';
} else {
    $cssFile = 'assets/app.css'; // フォールバック
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIコンポーネント チートシート（動的版）</title>
    <link rel="stylesheet" href="/build/<?php echo $cssFile; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- インラインスタイルは同じ -->
    <style>
        .component-section {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        /* 他のスタイルも同様... */
    </style>
</head>
<body class="bg-gray-50">
    <!-- HTMLコンテンツは同じ -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">📚 UIコンポーネント チートシート（動的版）</h1>
            <p class="text-lg text-gray-600">自動的に最新のCSSファイルを読み込みます</p>
        </header>
        
        <div class="bg-white rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold mb-2">現在のCSSファイル</h2>
            <p class="text-sm text-gray-600">読み込み中: <code>/build/<?php echo $cssFile; ?></code></p>
        </div>
        
        <!-- ボタンテスト -->
        <div class="bg-white rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">ボタンテスト</h2>
            <div class="space-x-4">
                <button class="btn-base btn-create btn-m">
                    <i class="fas fa-plus mr-2"></i>作成
                </button>
                <button class="btn-base btn-update btn-m">
                    <i class="fas fa-edit mr-2"></i>更新
                </button>
                <button class="btn-base btn-back btn-m">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </button>
            </div>
        </div>
    </div>
</body>
</html>