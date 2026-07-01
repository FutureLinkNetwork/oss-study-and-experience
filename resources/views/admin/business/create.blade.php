@extends('layouts.app')

@section('title', '事業者新規登録 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-red-100">
	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ">
        <!-- パンくずリスト -->
        <nav class="mt-4 text-sm">
            <ol class="flex space-x-2 text-gray-500">
				<li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('admin.business.index') }}" class="hover:text-gray-700">事業者管理</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>事業者新規登録</span></li>
            </ol>
        </nav>		
	
	<div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8 ">

    <!-- 事業者情報入力フォーム -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-building text-gray-400 mr-2"></i>
                事業者情報入力
            </h2>
        </div>
        
        @include('admin.business.form', [
            'action' => route('admin.business.store'),
            'business' => null,
            'formToken' => $formToken ?? session('form_access_token')
        ])
    </div>
</div>
@endsection