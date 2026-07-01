@extends('layouts.app')

@section('title', '管理画面 - ' . $subdomain->system_name)

@section('content')
<div class="min-h-screen bg-red-100">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- 機能メニュー -->
    @if(count($availableMenus) > 0)
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @foreach($availableMenus as $categoryName => $menus)
            @if(count($menus) > 0)
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 pb-2 border-b-2 border-gray-300">
                    {{ $categoryName }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($menus as $menu)
                    <a href="{{ $menu['route'] !== '#' ? route($menu['route']) : '#' }}" 
                       class="group relative block p-6 bg-gray-50 rounded-lg border-2 border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 {{ $menu['route'] === '#' ? 'opacity-50 cursor-not-allowed' : '' }}">
                        @if(isset($menu['badge']) && $menu['badge'] > 0)
                            <span class="absolute top-3 right-3 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 text-xs font-semibold text-white bg-red-500 rounded-full">
                                {{ $menu['badge'] }}
                            </span>
                        @endif
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white group-hover:bg-blue-600">
                                    <span class="text-2xl">{{ $menu['icon'] }}</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-900">
                                    {{ $menu['name'] }}
                                    @if($menu['route'] === '#')
                                        <span class="text-xs text-gray-500">(準備中)</span>
                                    @endif
                                </h3>
                                <p class="text-sm text-gray-500 group-hover:text-blue-700">
                                    {{ $menu['description'] }}
                                </p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    利用可能な機能がありません
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>現在の権限では管理機能を利用できません。管理者にお問い合わせください。</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection