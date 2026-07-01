<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class DefaultController extends Controller
{
    use HandlesAuth;

    /**
     * ランディングページ表示
     */
    public function index(Request $request)
    {
        // サブドメインを取得（統一処理を使用）
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
			abort(404);
		}

        // 初期表示お知らせデータを取得
        $notices = Notice::getPublicNotices($subdomain->id, 5);
        $totalNoticesCount = Notice::getPublicNoticesCount($subdomain->id);

        // サブドメイン固有のビューを決定
        $viewName = 'default.landing';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.landing';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain', 'notices', 'totalNoticesCount'));
    }
}
