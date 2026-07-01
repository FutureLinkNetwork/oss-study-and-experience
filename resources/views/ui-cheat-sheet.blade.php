@extends('layouts.app')

@section('title', 'UIコンポーネント チートシート - 習い事クーポン管理システム')

@section('content')
<style>
    .component-section {
        background: white;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    .code-sample {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        padding: 12px;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 13px;
        margin-top: 8px;
        overflow-x: auto;
    }
    .grid-demo {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin: 16px 0;
    }
    .example-item {
        text-align: center;
        padding: 16px;
        border: 1px dashed #d1d5db;
        border-radius: 4px;
    }
    .size-comparison {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin: 12px 0;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <header class="mb-12 text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">📚 UIコンポーネント チートシート</h1>
        <p class="text-lg text-gray-600">習い事クーポン管理システム - カスタムTailwind CSSクラス一覧</p>
        <div class="mt-6 text-sm text-gray-500">
            <p>最終更新: {{ date('Y年m月d日') }} | モジュール式クラス設計 (btn-base + btn-[type] + btn-[size])</p>
        </div>
    </header>

    <!-- ボタンコンポーネント -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-mouse-pointer text-blue-500 mr-2"></i>
            ボタンコンポーネント
        </h2>
        
        <h3 class="text-lg font-semibold text-gray-800 mb-4">基本構造</h3>
        <p class="text-gray-600 mb-4">すべてのボタンは <code class="bg-gray-100 px-2 py-1 rounded">btn-base</code> + タイプクラス + サイズクラスの組み合わせで構成されます。</p>
        
        <!-- ボタンタイプ -->
        <h4 class="text-md font-medium text-gray-700 mb-3">ボタンタイプ</h4>
        <div class="grid-demo">
            <div class="example-item">
                <button class="btn-base btn-create btn-m mb-2">
                    <i class="fas fa-plus mr-2"></i>作成・追加
                </button>
                <div class="code-sample">btn-base btn-create btn-m</div>
                <p class="text-xs text-gray-500 mt-1">緑色 - 新規作成、追加処理用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-update btn-m mb-2">
                    <i class="fas fa-edit mr-2"></i>更新・編集
                </button>
                <div class="code-sample">btn-base btn-update btn-m</div>
                <p class="text-xs text-gray-500 mt-1">黄色 - 編集、更新処理用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-back btn-m mb-2">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </button>
                <div class="code-sample">btn-base btn-back btn-m</div>
                <p class="text-xs text-gray-500 mt-1">グレー - 戻る、キャンセル用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-cancel btn-m mb-2">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </button>
                <div class="code-sample">btn-base btn-cancel btn-m</div>
                <p class="text-xs text-gray-500 mt-1">薄グレー - 中性的なキャンセル用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-disable btn-m mb-2">
                    <i class="fas fa-ban mr-2"></i>無効化
                </button>
                <div class="code-sample">btn-base btn-disable btn-m</div>
                <p class="text-xs text-gray-500 mt-1">赤色 - 削除、無効化用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-search btn-m mb-2">
                    <i class="fas fa-search mr-2"></i>検索
                </button>
                <div class="code-sample">btn-base btn-search btn-m</div>
                <p class="text-xs text-gray-500 mt-1">青色 - 検索、実行用</p>
            </div>
            
            <div class="example-item">
                <button class="btn-base btn-duplicate btn-m mb-2">
                    <i class="fas fa-copy mr-2"></i>複製
                </button>
                <div class="code-sample">btn-base btn-duplicate btn-m</div>
                <p class="text-xs text-gray-500 mt-1">紫色 - 複製、コピー用</p>
            </div>
        </div>

        <!-- ボタンサイズ -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">ボタンサイズ比較</h4>
        <div class="size-comparison">
            <button class="btn-base btn-create btn-xs">
                <i class="fas fa-plus mr-1"></i>XS
            </button>
            <button class="btn-base btn-create btn-s">
                <i class="fas fa-plus mr-1"></i>Small
            </button>
            <button class="btn-base btn-create btn-m">
                <i class="fas fa-plus mr-2"></i>Medium
            </button>
            <button class="btn-base btn-create btn-l">
                <i class="fas fa-plus mr-2"></i>Large
            </button>
            <button class="btn-base btn-create btn-xl">
                <i class="fas fa-plus mr-2"></i>Extra Large
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
            <div class="text-center">
                <div class="code-sample">btn-xs</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.25rem 0.5rem<br>font-size: 0.75rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">btn-s</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.375rem 0.75rem<br>font-size: 0.875rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">btn-m</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.5rem 1rem<br>font-size: 0.875rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">btn-l</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.75rem 1.5rem<br>font-size: 1rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">btn-xl</div>
                <p class="text-xs text-gray-500 mt-1">padding: 1rem 2rem<br>font-size: 1.125rem</p>
            </div>
        </div>

        <!-- 無効状態 -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">無効状態</h4>
        <div class="size-comparison">
            <button class="btn-base btn-create btn-m" disabled>
                <i class="fas fa-plus mr-2"></i>無効状態
            </button>
            <button class="btn-base btn-disabled btn-m">
                <i class="fas fa-ban mr-2"></i>無効専用
            </button>
        </div>
    </section>

    <!-- ラベルコンポーネント -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-tags text-green-500 mr-2"></i>
            ラベルコンポーネント
        </h2>
        
        <h3 class="text-lg font-semibold text-gray-800 mb-4">基本構造</h3>
        <p class="text-gray-600 mb-4">すべてのラベルは <code class="bg-gray-100 px-2 py-1 rounded">label-base</code> + タイプクラス + サイズクラスの組み合わせで構成されます。</p>
        
        <!-- ラベルタイプ -->
        <h4 class="text-md font-medium text-gray-700 mb-3">ラベルタイプ</h4>
        <div class="grid-demo">
            <div class="example-item">
                <span class="label-base label-active label-m mb-2">有効</span>
                <div class="code-sample">label-base label-active label-m</div>
                <p class="text-xs text-gray-500 mt-1">緑色 - アクティブ、有効状態</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-inactive label-m mb-2">無効</span>
                <div class="code-sample">label-base label-inactive label-m</div>
                <p class="text-xs text-gray-500 mt-1">赤色 - 非アクティブ、無効状態</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-disable label-m mb-2">停止</span>
                <div class="code-sample">label-base label-disable label-m</div>
                <p class="text-xs text-gray-500 mt-1">グレー - 無効化、停止状態</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-pending label-m mb-2">保留中</span>
                <div class="code-sample">label-base label-pending label-m</div>
                <p class="text-xs text-gray-500 mt-1">黄色 - 保留、待機状態</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-info label-m mb-2">情報</span>
                <div class="code-sample">label-base label-info label-m</div>
                <p class="text-xs text-gray-500 mt-1">青色 - 情報、通知用</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-warning label-m mb-2">警告</span>
                <div class="code-sample">label-base label-warning label-m</div>
                <p class="text-xs text-gray-500 mt-1">オレンジ色 - 警告、注意用</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-neutral label-m mb-2">中立</span>
                <div class="code-sample">label-base label-neutral label-m</div>
                <p class="text-xs text-gray-500 mt-1">中性グレー - 中立、一般用</p>
            </div>
            
            <div class="example-item">
                <span class="label-base label-duplicate label-m mb-2">複製</span>
                <div class="code-sample">label-base label-duplicate label-m</div>
                <p class="text-xs text-gray-500 mt-1">薄紫色 - 複製、コピー用</p>
            </div>
        </div>

        <!-- ラベルサイズ -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">ラベルサイズ比較</h4>
        <div class="size-comparison">
            <span class="label-base label-active label-xs">XS</span>
            <span class="label-base label-active label-s">Small</span>
            <span class="label-base label-active label-m">Medium</span>
            <span class="label-base label-active label-l">Large</span>
            <span class="label-base label-active label-xl">Extra Large</span>
        </div>
    </section>

    <!-- アラートメッセージコンポーネント -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
            アラートメッセージコンポーネント
        </h2>
        
        <h3 class="text-lg font-semibold text-gray-800 mb-4">基本構造</h3>
        <p class="text-gray-600 mb-4">すべてのアラートは <code class="bg-gray-100 px-2 py-1 rounded">alert-base</code> + タイプクラス + サイズクラスの組み合わせで構成されます。</p>
        
        <!-- アラートタイプ -->
        <h4 class="text-md font-medium text-gray-700 mb-3">アラートタイプ</h4>
        <div class="space-y-4">
            <div class="alert-base alert-success alert-m">
                <div class="alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="alert-message">
                    操作が正常に完了しました。
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="code-sample">
&lt;div class="alert-base alert-success alert-m"&gt;
    &lt;div class="alert-icon"&gt;&lt;i class="fas fa-check-circle"&gt;&lt;/i&gt;&lt;/div&gt;
    &lt;div class="alert-message"&gt;操作が正常に完了しました。&lt;/div&gt;
    &lt;button class="alert-close"&gt;&lt;i class="fas fa-times"&gt;&lt;/i&gt;&lt;/button&gt;
&lt;/div&gt;
            </div>
            
            <div class="alert-base alert-error alert-m">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-message">
                    エラーが発生しました。入力内容をご確認ください。
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="code-sample">
&lt;div class="alert-base alert-error alert-m"&gt;
    &lt;div class="alert-icon"&gt;&lt;i class="fas fa-exclamation-circle"&gt;&lt;/i&gt;&lt;/div&gt;
    &lt;div class="alert-message"&gt;エラーが発生しました。入力内容をご確認ください。&lt;/div&gt;
    &lt;button class="alert-close"&gt;&lt;i class="fas fa-times"&gt;&lt;/i&gt;&lt;/button&gt;
&lt;/div&gt;
            </div>
            
            <div class="alert-base alert-info alert-m">
                <div class="alert-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-message">
                    この操作は元に戻すことができません。
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="code-sample">
&lt;div class="alert-base alert-info alert-m"&gt;
    &lt;div class="alert-icon"&gt;&lt;i class="fas fa-info-circle"&gt;&lt;/i&gt;&lt;/div&gt;
    &lt;div class="alert-message"&gt;この操作は元に戻すことができません。&lt;/div&gt;
    &lt;button class="alert-close"&gt;&lt;i class="fas fa-times"&gt;&lt;/i&gt;&lt;/button&gt;
&lt;/div&gt;
            </div>
        </div>

        <!-- アラートサイズ -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">アラートサイズ比較</h4>
        <div class="space-y-3">
            <div class="alert-base alert-success alert-s">
                <div class="alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="alert-message">
                    Small サイズのアラートメッセージです
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="alert-base alert-info alert-m">
                <div class="alert-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-message">
                    Medium サイズのアラートメッセージです（デフォルト）
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="alert-base alert-error alert-l">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-message">
                    Large サイズのアラートメッセージです
                </div>
                <button class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div class="text-center">
                <div class="code-sample">alert-s</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.5rem 0.75rem<br>font-size: 0.75rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">alert-m</div>
                <p class="text-xs text-gray-500 mt-1">padding: 0.75rem 1rem<br>font-size: 0.875rem</p>
            </div>
            <div class="text-center">
                <div class="code-sample">alert-l</div>
                <p class="text-xs text-gray-500 mt-1">padding: 1rem 1.25rem<br>font-size: 1rem</p>
            </div>
        </div>

        <!-- セッションメッセージでの使用例 -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">セッションメッセージでの使用例</h4>
        <div class="bg-gray-50 p-4 rounded-lg">
            <h5 class="text-sm font-medium text-gray-800 mb-3">Bladeテンプレートでの実装例</h5>
            <div class="code-sample">
@if(session('success'))
    &lt;div class="alert-base alert-success alert-m mb-6"&gt;
        &lt;div class="alert-icon"&gt;
            &lt;i class="fas fa-check-circle"&gt;&lt;/i&gt;
        &lt;/div&gt;
        &lt;div class="alert-message"&gt;
            {{ session('success') }}
        &lt;/div&gt;
        &lt;button class="alert-close" onclick="this.parentElement.style.display='none'"&gt;
            &lt;i class="fas fa-times"&gt;&lt;/i&gt;
        &lt;/button&gt;
    &lt;/div&gt;
@endif

@if(session('error'))
    &lt;div class="alert-base alert-error alert-m mb-6"&gt;
        &lt;div class="alert-icon"&gt;
            &lt;i class="fas fa-exclamation-circle"&gt;&lt;/i&gt;
        &lt;/div&gt;
        &lt;div class="alert-message"&gt;
            {{ session('error') }}
        &lt;/div&gt;
        &lt;button class="alert-close" onclick="this.parentElement.style.display='none'"&gt;
            &lt;i class="fas fa-times"&gt;&lt;/i&gt;
        &lt;/button&gt;
    &lt;/div&gt;
@endif

@if(session('info'))
    &lt;div class="alert-base alert-info alert-m mb-6"&gt;
        &lt;div class="alert-icon"&gt;
            &lt;i class="fas fa-info-circle"&gt;&lt;/i&gt;
        &lt;/div&gt;
        &lt;div class="alert-message"&gt;
            {{ session('info') }}
        &lt;/div&gt;
        &lt;button class="alert-close" onclick="this.parentElement.style.display='none'"&gt;
            &lt;i class="fas fa-times"&gt;&lt;/i&gt;
        &lt;/button&gt;
    &lt;/div&gt;
@endif
            </div>
        </div>

        <!-- 推奨アイコン -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">推奨アイコン</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 border rounded-lg">
                <div class="text-green-600 text-2xl mb-2">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="text-sm font-medium">Success</div>
                <div class="text-xs text-gray-500 mt-1">fas fa-check-circle</div>
            </div>
            <div class="text-center p-4 border rounded-lg">
                <div class="text-red-600 text-2xl mb-2">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="text-sm font-medium">Error</div>
                <div class="text-xs text-gray-500 mt-1">fas fa-exclamation-circle</div>
            </div>
            <div class="text-center p-4 border rounded-lg">
                <div class="text-blue-600 text-2xl mb-2">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="text-sm font-medium">Info</div>
                <div class="text-xs text-gray-500 mt-1">fas fa-info-circle</div>
            </div>
        </div>
    </section>

    <!-- フィールドコンポーネント -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-edit text-indigo-500 mr-2"></i>
            フィールドコンポーネント
        </h2>
        
        <h3 class="text-lg font-semibold text-gray-800 mb-4">基本構造</h3>
        <p class="text-gray-600 mb-4">すべてのフィールドは <code class="bg-gray-100 px-2 py-1 rounded">field-base</code> + 状態クラス + 幅クラスの組み合わせで構成されます。</p>
        
        <!-- フィールド状態 -->
        <h4 class="text-md font-medium text-gray-700 mb-3">フィールド状態</h4>
        <div class="grid-demo">
            <div class="example-item">
                <input type="text" class="field-base field-w-100 mb-2" placeholder="通常のフィールド" value="サンプルテキスト">
                <div class="code-sample">field-base field-w-100</div>
                <p class="text-xs text-gray-500 mt-1">標準 - 通常の入力フィールド</p>
            </div>
            
            <div class="example-item">
                <input type="text" class="field-base field-w-100 error mb-2" placeholder="エラー状態" value="無効な値">
                <div class="code-sample">field-base field-w-100 error</div>
                <p class="text-xs text-gray-500 mt-1">赤色 - エラー、バリデーション失敗時</p>
            </div>
            
            <div class="example-item">
                <input type="text" class="field-base field-w-100 duplicate mb-2" placeholder="複製フィールド" value="コピー元データ">
                <div class="code-sample">field-base field-w-100 duplicate</div>
                <p class="text-xs text-gray-500 mt-1">薄紫色 - 複製、コピー関連フィールド</p>
            </div>
            
            <div class="example-item">
                <input type="text" class="field-base field-w-100 mb-2" placeholder="無効状態" value="編集不可" disabled>
                <div class="code-sample">field-base field-w-100 (disabled)</div>
                <p class="text-xs text-gray-500 mt-1">グレー - 無効、編集不可状態</p>
            </div>
        </div>

        <!-- フィールド幅 -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">フィールド幅クラス</h4>
        <div class="space-y-3">
            <div class="flex items-center space-x-4">
                <input type="text" class="field-base field-w-10" placeholder="10%">
                <span class="text-sm text-gray-500">field-w-10 (10%)</span>
            </div>
            <div class="flex items-center space-x-4">
                <input type="text" class="field-base field-w-30" placeholder="30%">
                <span class="text-sm text-gray-500">field-w-30 (30%)</span>
            </div>
            <div class="flex items-center space-x-4">
                <input type="text" class="field-base field-w-50" placeholder="50%">
                <span class="text-sm text-gray-500">field-w-50 (50%)</span>
            </div>
            <div class="flex items-center space-x-4">
                <input type="text" class="field-base field-w-100" placeholder="100%">
                <span class="text-sm text-gray-500">field-w-100 (100%)</span>
            </div>
        </div>

        <!-- フィールドタイプ -->
        <h4 class="text-md font-medium text-gray-700 mb-3 mt-8">フィールドタイプ</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="field-label">テキストエリア</label>
                <textarea class="field-base field-textarea field-w-100" placeholder="複数行のテキスト入力"></textarea>
                <div class="code-sample mt-2">field-base field-textarea field-w-100</div>
            </div>
            
            <div>
                <label class="field-label">セレクトボックス</label>
                <select class="field-base field-select field-w-100">
                    <option>選択してください</option>
                    <option>オプション1</option>
                    <option>オプション2</option>
                </select>
                <div class="code-sample mt-2">field-base field-select field-w-100</div>
            </div>
        </div>
    </section>

    <!-- 実際の使用例 -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-code text-purple-500 mr-2"></i>
            実際の使用例
        </h2>
        
        <!-- テーブル行アクション -->
        <h4 class="text-md font-medium text-gray-700 mb-3">テーブル行アクション</h4>
        <div class="bg-white border rounded-lg overflow-hidden mb-6">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">事業者名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ステータス</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">アクション</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            株式会社サンプル
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="label-base label-active label-xs">有効</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="#" class="btn-base btn-update btn-xs">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </a>
                                <button class="btn-base btn-duplicate btn-xs">
                                    <i class="fas fa-copy mr-1"></i>複製
                                </button>
                                <button class="btn-base btn-disable btn-xs">
                                    <i class="fas fa-times mr-1"></i>無効化
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            個人事業主 田中
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="label-base label-disable label-xs">無効</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="#" class="btn-base btn-update btn-xs">
                                    <i class="fas fa-edit mr-1"></i>編集
                                </a>
                                <button class="btn-base btn-create btn-xs">
                                    <i class="fas fa-check mr-1"></i>有効化
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- フォームボタン -->
        <h4 class="text-md font-medium text-gray-700 mb-3">フォームボタン</h4>
        <div class="bg-white border rounded-lg p-6 mb-6">
            <div class="flex justify-end space-x-4">
                <a href="#" class="btn-base btn-back btn-m">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
                <button type="button" class="btn-base btn-cancel btn-m">
                    <i class="fas fa-times mr-2"></i>キャンセル
                </button>
                <button type="submit" class="btn-base btn-create btn-m">
                    <i class="fas fa-save mr-2"></i>保存
                </button>
            </div>
        </div>

        <!-- 複製フォーム例 -->
        <h4 class="text-md font-medium text-gray-700 mb-3">複製フォーム例</h4>
        <div class="bg-white border rounded-lg p-6 mb-6">
            <div class="mb-4">
                <label class="field-label">複製元コース名 <span class="label-base label-duplicate label-xs ml-2">複製</span></label>
                <input type="text" class="field-base field-w-100 duplicate" value="プログラミング基礎コース" readonly>
            </div>
            <div class="mb-4">
                <label class="field-label required">新しいコース名</label>
                <input type="text" class="field-base field-w-100" placeholder="新しいコース名を入力してください">
            </div>
            <div class="flex justify-end space-x-4">
                <a href="#" class="btn-base btn-back btn-m">
                    <i class="fas fa-arrow-left mr-2"></i>戻る
                </a>
                <button type="submit" class="btn-base btn-duplicate btn-m">
                    <i class="fas fa-copy mr-2"></i>複製して作成
                </button>
            </div>
        </div>

        <!-- ページヘッダー -->
        <h4 class="text-md font-medium text-gray-700 mb-3">ページヘッダー</h4>
        <div class="bg-white border rounded-lg p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">事業者管理</h1>
                    <p class="mt-1 text-gray-600">登録されている事業者の一覧と管理</p>
                </div>
                <a href="#" class="btn-base btn-create btn-m">
                    <i class="fas fa-plus mr-2"></i>新規事業者登録
                </a>
            </div>
        </div>
    </section>

    <!-- CSS設定情報 -->
    <section class="component-section">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-200 pb-2">
            <i class="fas fa-cog text-gray-500 mr-2"></i>
            CSS設定情報
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-3">ファイル構成</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <ul class="space-y-2 text-sm">
                        <li><strong>ソース:</strong> <code>resources/css/app.css</code></li>
                        <li><strong>ビルド:</strong> <code>public/build/assets/app-[hash].css</code></li>
                        <li><strong>設定:</strong> <code>vite.config.js</code></li>
                        <li><strong>テンプレート:</strong> <code>resources/views/layouts/app.blade.php</code></li>
                    </ul>
                </div>
            </div>
            
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-3">ビルドコマンド</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <ul class="space-y-2 text-sm font-mono">
                        <li><strong>開発:</strong> npm run dev</li>
                        <li><strong>本番:</strong> npm run build</li>
                        <li><strong>監視:</strong> npm run watch</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <h4 class="text-md font-medium text-gray-700 mb-3">カスタマイズ方法</h4>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <ol class="list-decimal list-inside space-y-2 text-sm">
                    <li><code>resources/css/app.css</code> でクラスを編集</li>
                    <li><code>npm run build</code> でCSSをリビルド</li>
                    <li>ブラウザでリロードして変更を確認</li>
                    <li>必要に応じて新しいタイプやサイズを追加</li>
                </ol>
            </div>
        </div>
    </section>

    <!-- フッター -->
    <footer class="text-center py-8 border-t border-gray-200 mt-12">
        <p class="text-gray-500 text-sm">
            © 2025 習い事クーポン管理システム | 
            モジュール式UIコンポーネント設計
        </p>
    </footer>
</div>
@endsection