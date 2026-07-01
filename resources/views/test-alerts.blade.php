@extends('layouts.app')

@section('title', 'アラートテスト - 習い事クーポン管理システム')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">アラートメッセージテスト</h1>
    
    <!-- テスト用ボタン -->
    <div class="mb-8 space-x-4">
        <a href="{{ route('test-alerts') }}?type=success" class="btn-base btn-create btn-m">
            成功メッセージをテスト
        </a>
        <a href="{{ route('test-alerts') }}?type=error" class="btn-base btn-disable btn-m">
            エラーメッセージをテスト
        </a>
        <a href="{{ route('test-alerts') }}?type=info" class="btn-base btn-search btn-m">
            情報メッセージをテスト
        </a>
    </div>

    <!-- アラート表示エリア -->
    @if(session('success'))
        <div class="alert-base alert-success alert-m mb-6">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="alert-message">
                {{ session('success') }}
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-base alert-error alert-m mb-6">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="alert-message">
                {{ session('error') }}
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert-base alert-info alert-m mb-6">
            <div class="alert-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="alert-message">
                {{ session('info') }}
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- サイズ比較テスト -->
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-900">サイズ比較</h2>
        
        <div class="alert-base alert-success alert-s">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="alert-message">
                Small サイズのアラートメッセージ
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
                Medium サイズのアラートメッセージ（デフォルト）
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
                Large サイズのアラートメッセージ
            </div>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
@endsection