<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class UserNoticeController extends Controller
{
    use \App\Http\Controllers\Concerns\StreamsNoticeAttachment;
    use HandlesAuth;

    /**
     * 利用者向けお知らせ詳細画面を表示
     */
    public function show(Request $request, string $noticeId)
    {
        // サブドメインを取得
        $subdomain = $this->getCurrentSubdomain($request);

        // 利用者向けお知らせを取得（公開状態・サブドメイン・削除状態をチェック）
        $notice = Notice::query()
            ->notDeleted()
            ->published()
            ->userDashboard()
            ->forSubdomain($subdomain->id)
            ->where('id', $noticeId)
            ->firstOrFail();

        $type = 'User';

        return view('notices.show', compact('subdomain', 'notice', 'type'));
    }

    /**
     * 利用者: お知らせ添付ファイルをダウンロード
     */
    public function downloadAttachment(Request $request, string $noticeId)
    {
        $subdomain = $this->getCurrentSubdomain($request);

        $notice = Notice::query()
            ->notDeleted()
            ->published()
            ->userDashboard()
            ->forSubdomain($subdomain->id)
            ->where('id', $noticeId)
            ->firstOrFail();

        if (! $notice->hasAttachment()) {
            abort(404, '添付ファイルがありません。');
        }

        return $this->streamNoticeAttachment($notice);
    }
}
