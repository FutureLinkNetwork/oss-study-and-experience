@extends('default.layout')

@section('title', '習い事クーポン管理システム')

@section('content')
<div class="min-h-screen">
    <!-- ヒーロセクション -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <div class="mb-8">
                    <i class="fas fa-graduation-cap text-6xl mb-6"></i>
                </div>
                <h1 class="text-4xl font-bold sm:text-5xl md:text-6xl mb-6">
                    習い事クーポン管理システム
                </h1>
                @if(isset($subdomain))
                <h2 class="text-2xl font-semibold mb-8">
                    {{ $subdomain->display_name }}
                </h2>
                @endif
                <p class="text-xl md:text-2xl mb-12 max-w-3xl mx-auto">
                    子どもたちの習い事を支援するクーポン制度の<br>
                    申請・管理を効率的に行うためのシステムです
                </p>
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 transition duration-150 ease-in-out">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        ログイン
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 機能紹介セクション -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">システムの特徴</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    習い事クーポン制度の運営に必要な機能を包括的に提供
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- 特徴1 -->
                <div class="text-center">
                    <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">ユーザー管理</h3>
                    <p class="text-gray-600">
                        利用者・事業者・管理者の<br>
                        階層的な権限管理
                    </p>
                </div>
                
                <!-- 特徴2 -->
                <div class="text-center">
                    <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-ticket-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">クーポン管理</h3>
                    <p class="text-gray-600">
                        発行から利用まで<br>
                        クーポンの全工程を管理
                    </p>
                </div>
                
                <!-- 特徴3 -->
                <div class="text-center">
                    <div class="bg-purple-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">レポート機能</h3>
                    <p class="text-gray-600">
                        利用状況の分析と<br>
                        詳細なレポート作成
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- お知らせセクション -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">お知らせ</h2>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="mb-4">
                    <i class="fas fa-construction text-yellow-500 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">システム開発中</h3>
                <p class="text-gray-600">
                    現在、システムの構築を進めております。<br>
                    順次機能を追加し、より使いやすいシステムを提供予定です。
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* カスタムスタイル */
.bg-gradient-to-r {
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
}
</style>
@endpush

@push('scripts')
<script>
// カスタムJavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('習い事クーポン管理システム ランディングページ読み込み完了');
});
</script>
@endpush