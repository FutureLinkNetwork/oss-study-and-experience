@extends('layouts.app')

@section('title', '登録内容の確認 - 習い事クーポン管理システム')

@section('content')
<div class="min-h-screen bg-blue-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mt-4 text-sm">
            <ol class="flex flex-wrap gap-x-2 gap-y-1 text-gray-500">
                <li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">トップページ</a></li>
                <li><span class="mx-2">/</span></li>
                <li><span>登録内容</span></li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 mt-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">登録内容</h2>
                    </div>
                </div>
                <div class="px-6 py-6 space-y-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">申請者種別</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $applicantTypeLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">事業者名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->business_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">事業者名（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->business_name_kana ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者役職名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->representative_title ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->representative_name_full }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者役職名（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->representative_title_kana ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">代表者名（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->representative_name_kana_full }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">郵便番号</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">
                                @if($businessInfo->postal_code)
                                    〒{{ $businessInfo->postal_code }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">住所</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line ml-4">{{ $addressDisplay !== '' ? $addressDisplay : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">電話番号</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">FAX番号</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->fax ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">メールアドレス</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ウェブサイトURL</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all ml-4">
                                @if($businessInfo->website_url)
                                    <a href="{{ $businessInfo->website_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800">{{ $businessInfo->website_url }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">連絡先：担当者名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->contact_person ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">連絡先：電話番号</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->contact_phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">文書等送付先：宛名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->document_person ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">文書等送付先：住所</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line ml-4">{{ $businessInfo->document_address ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">連絡先：営業時間</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line ml-4">{{ $businessInfo->business_hours ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">連絡先：定休日</dt>
                            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line ml-4">{{ $businessInfo->holiday ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">銀行名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $bankNameDisplay !== '' ? $bankNameDisplay : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">支店名</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $branchNameDisplay !== '' ? $branchNameDisplay : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">口座種別</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->account_type ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">口座番号</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->account_number ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">口座名義（カナ）</dt>
                            <dd class="mt-1 text-sm text-gray-900 ml-4">{{ $businessInfo->account_holder_name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
