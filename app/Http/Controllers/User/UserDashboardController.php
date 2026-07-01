<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Notice;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
	use HandlesAuth;
    /**
     * 利用者ダッシュボード画面を表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 初回ログイン（last_login_atがnull）の場合はパスワード変更画面へ
        if ($user->last_login_at === null) {
            return redirect()->route('user.password.change');
        }

		// サブドメインを取得
		$subdomain = $this->getCurrentSubdomain($request);

        // 事業者向けお知らせデータを取得（ページネーション対応）
        $notices = Notice::query()
            ->notDeleted()
            ->published()
            ->userDashboard()
            ->forSubdomain($subdomain->id)
            ->orderBy('id', 'desc')
            ->paginate(5);

        return view('user.dashboard', compact('subdomain', 'notices'));
    }
}
