@extends('layouts.user')

@section('title', '利用者申請完了')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto text-center">
        <!-- 成功アイコン -->
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                <i class="fas fa-check text-green-600 text-2xl"></i>
            </div>
        </div>

        <!-- タイトル -->
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            利用者申請が完了しました
        </h1>

        <!-- メッセージ -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
            <div class="text-green-800">
                <h2 class="text-lg font-semibold mb-2">申請受付完了</h2>
                <p class="text-sm leading-relaxed">
                    利用者申請を受け付けました。<br>
                    審査結果につきましては、後日ご連絡いたします。<br>
                    ご不明な点がございましたら、お気軽にお問い合わせください。<br>
                     「習い事等の申込」は直接事業者にお申し込みください。
                </p>
            </div>
        </div>

        <!-- 次のステップ -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <div class="text-blue-800">
                <h3 class="text-lg font-semibold mb-3">今後の流れ</h3>
                <div class="text-left space-y-2 text-sm">
                    <div class="flex items-start">
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-200 text-blue-800 rounded-full text-xs font-semibold mr-3 mt-0.5">1</span>
                        <p>申請内容の審査を行います。</p>
                    </div>
                    <div class="flex items-start">
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-200 text-blue-800 rounded-full text-xs font-semibold mr-3 mt-0.5">2</span>
                        <p>審査結果をメールにてご連絡します。<br>
                        ※交付を決定された場合は、ID と仮パスワードが発行されます。<br>
                        無くさないようにご注意ください。</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 注意事項 -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
            <div class="text-yellow-800">
                <h3 class="text-lg font-semibold mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>重要なお知らせ
                </h3>
                <div class="text-left text-sm space-y-1">
                    <p>• 審査中に追加の書類提出をお願いする場合があります</p>
                    <p>• 申請内容に不備がある場合は、修正をお願いすることがあります</p>
                    <p>• 申請内容に変更があった場合は、速やかにご連絡ください。</p>
                </div>
            </div>
        </div>

        <!-- アクションボタン -->
        <div class="space-y-4">
            <a href="{{ route('welcome') }}" class="btn-base btn-back btn-l w-full sm:w-auto">
                <i class="fas fa-home mr-2"></i>トップページに戻る
            </a>
            
            <div class="text-gray-600 text-sm">
                <p><a href="{{ route('contact') }}" target="_blank">お問い合わせフォーム</a></p>
            </div>
        </div>
    </div>
</div>
@endsection

