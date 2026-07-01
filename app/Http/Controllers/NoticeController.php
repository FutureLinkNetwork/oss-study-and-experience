<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    use \App\Http\Controllers\Concerns\StreamsNoticeAttachment;
    use HandlesAuth;

    /**
     * お知らせ詳細表示
     */
    public function show(Request $request, string $noticeId)
    {
        // サブドメインを取得（統一処理を使用）
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // お知らせを取得（公開状態・サブドメイン・削除状態をチェック）
        $notice = Notice::query()
            ->notDeleted()
            ->published()
            ->publicDisplay()
            ->forSubdomain($subdomain->id)
            ->where('id', $noticeId)
            ->firstOrFail();

        // サブドメイン固有のビューを決定
        $viewName = 'default.notice.show';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.notice.show';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain', 'notice'));
    }

    /**
     * LP: お知らせ添付ファイルをダウンロード（公開・公開期間内・同一サブドメインのみ）
     */
    public function downloadAttachment(Request $request, string $noticeId)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        $notice = Notice::query()
            ->notDeleted()
            ->published()
            ->publicDisplay()
            ->forSubdomain($subdomain->id)
            ->where('id', $noticeId)
            ->firstOrFail();

        if (! $notice->hasAttachment()) {
            abort(404, '添付ファイルがありません。');
        }

        return $this->streamNoticeAttachment($notice);
    }

    /**
     * お知らせ追加読み込み（Ajax API）
     */
    public function loadMore(Request $request)
    {
        // サブドメインを取得（統一処理を使用）
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Subdomain not found'], 404);
        }

        // パラメータ取得
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = max(1, min(10, (int) $request->input('limit', 10)));

        // お知らせデータを取得
        $notices = Notice::getPublicNotices($subdomain->id, $limit, $offset);
        $totalCount = Notice::getPublicNoticesCount($subdomain->id);

        // レスポンス用にデータを整形
        $noticesData = $notices->map(function ($notice) {
            return [
                'id' => $notice->id,
                'title' => $notice->title,
                'notice_date_display' => $notice->notice_date_display,
                'show_on_user_dashboard' => $notice->show_on_user_dashboard,
                'show_on_business_dashboard' => $notice->show_on_business_dashboard,
                'target_labels' => array_map(function ($target) {
                    return [
                        'label' => $target['label'],
                        'class' => $target['class'],
                    ];
                }, $notice->target_labels),
                'url' => route('notices.show', $notice->id),
            ];
        });

        return response()->json([
            'notices' => $noticesData,
            'has_more' => ($offset + $limit) < $totalCount,
        ]);
    }
}
