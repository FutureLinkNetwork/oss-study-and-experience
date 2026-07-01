<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubdomainRequest;
use App\Models\AccountingReportDownload;
use App\Models\Beneficiary;
use App\Models\BusinessInfo;
use App\Models\Contact;
use App\Models\CourseRequest;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * 管理画面のダッシュボードを表示
     */
    public function index()
    {
        $user = \App\Models\User::with(['role', 'subdomain'])->find(Auth::id());

        // ユーザーの権限に応じて利用可能なメニューを決定
        $availableMenus = $this->getAvailableMenus($user);

        // サブドメイン情報取得
        $subdomain = $user->subdomain;

        return view('admin.dashboard', compact('user', 'availableMenus', 'subdomain'));
    }

    /**
     * ユーザーの権限に応じた利用可能メニューを取得
     */
    private function getAvailableMenus($user): array
    {
        $menus = [];

        // お問い合わせ管理（レベル60以上）
        if ($user->hasLevelOrAbove(60)) {
            $menus['お問い合わせ'][] = [
                'name' => 'お問い合わせ管理',
                'description' => 'お問い合わせの確認・管理',
                'route' => 'admin.contacts.index',
                'icon' => '📧',
                'permission_level' => 60,
                'badge' => Contact::where('subdomain_id', $user->subdomain_id)->unconfirmed()->count(),
            ];
            $menus['お問い合わせ'][] = [
                'name' => '問い合わせ（利用者・事業者）',
                'description' => '利用者・事業者からの問い合わせの確認・回答',
                'route' => 'admin.inquiries.index',
                'icon' => '✉️',
                'permission_level' => 60,
                'badge' => Inquiry::forSubdomain($user->subdomain_id)->where('status', 'pending')->count(),
            ];
        }

        // 習い事リクエスト管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['お問い合わせ'][] = [
                'name' => '習い事リクエスト管理',
                'description' => '習い事リクエストの確認・管理',
                'route' => 'admin.course-requests.index',
                'icon' => '📝',
                'permission_level' => 40,
                'badge' => CourseRequest::where('subdomain_id', $user->subdomain_id)->unconfirmed()->count(),
            ];
        }

        // 事業者管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['事業者管理'][] = [
                'name' => '事業者管理',
                'description' => '習い事事業者の管理',
                'route' => 'admin.business.index',
                'icon' => '🏢',
                'permission_level' => 40,
                'badge' => BusinessInfo::where('subdomain_id', $user->subdomain_id)->where('status', '未着手')->count(),
            ];
        }

        // 支払管理（会計用月次レポートの未DLバッジは支払集計に表示）
        if ($user->hasLevelOrAbove(40)) {
            $menus['支払管理'][] = [
                'name' => '支払集計',
                'description' => '事業者・教室別の支払集計一覧、会計用月次レポートのダウンロード',
                'route' => 'admin.payments.index',
                'icon' => '💰',
                'permission_level' => 40,
                'badge' => AccountingReportDownload::where('subdomain_id', $user->subdomain_id)->undownloaded()->count(),
            ];
        }

        // クーポンの利用状況管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['支払管理'][] = [
                'name' => 'クーポンの利用状況管理',
                'description' => 'クーポン利用データの一覧・詳細・修正',
                'route' => 'admin.coupon-usages.index',
                'icon' => '🎫',
                'permission_level' => 40,
            ];
        }

        // 利用者申請管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['利用者管理'][] = [
                'name' => '利用者申請管理',
                'description' => '利用者申請の確認・管理',
                'route' => 'admin.user-applications.index',
                'icon' => '📋',
                'permission_level' => 40,
            ];
        }

        // 利用者管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['利用者管理'][] = [
                'name' => '利用者管理',
                'description' => '利用者の管理・CSV取り込み',
                'route' => 'admin.beneficiaries.index',
                'icon' => '👤',
                'permission_level' => 40,
                'badge' => Beneficiary::where('subdomain_id', $user->subdomain_id)->whereIn('status', ['決定通知書未送信', '決定通知書送信待ち', '決定通知書送信失敗'])->count(),
            ];
        }

        // クーポン管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['利用者管理'][] = [
                'name' => 'クーポン管理',
                'description' => 'クーポンの発行・管理',
                'route' => 'admin.vouchers.index',
                'icon' => '🎫',
                'permission_level' => 40,
            ];
        }

        // システム管理（レベル80以上）
        if ($user->hasLevelOrAbove(80)) {
            $menus['システム関連'][] = [
                'name' => 'システム管理',
                'description' => 'システム管理',
                'route' => 'admin.subdomain.edit',
                'icon' => '⚙️',
                'permission_level' => 80,
            ];
        }

        // ユーザー管理（レベル60以上）
        if ($user->hasLevelOrAbove(60)) {
            $menus['システム関連'][] = [
                'name' => 'ユーザー管理',
                'description' => 'システムユーザーの管理',
                'route' => 'admin.users.index',
                'icon' => '👥',
                'permission_level' => 60,
            ];
        }

        // 習い事種別管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['システム関連'][] = [
                'name' => '習い事種別管理',
                'description' => '習い事の親分類・分類の管理',
                'route' => 'admin.course-categories.index',
                'icon' => '📚',
                'permission_level' => 40,
            ];
        }

        // お知らせ管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['システム関連'][] = [
                'name' => 'お知らせ管理',
                'description' => 'システムお知らせの作成・管理',
                'route' => 'admin.notices.index',
                'icon' => '📢',
                'permission_level' => 40,
            ];
        }

        // ダウンロード管理（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['システム関連'][] = [
                'name' => 'ダウンロード管理',
                'description' => '各種ダウンロード',
                'route' => 'admin.downloads.index',
                'icon' => '📥',
                'permission_level' => 40,
            ];
        }

        // レポート（レベル40以上）
        if ($user->hasLevelOrAbove(40)) {
            $menus['レポート'][] = [
                'name' => 'レポート',
                'description' => '各種レポート',
                'route' => 'admin.reports.index',
                'icon' => '📊',
                'permission_level' => 40,
            ];
        }

        return $menus;
    }

    /**
     * サブドメイン編集画面を表示
     */
    public function edit()
    {
        $user = \App\Models\User::with(['role', 'subdomain'])->find(Auth::id());

        // 権限チェック（レベル80以上）
        if (! $user->hasLevelOrAbove(80)) {
            abort(403, 'この機能にアクセスする権限がありません。');
        }

        // サブドメイン情報取得
        $subdomain = $user->subdomain;

        if (! $subdomain) {
            abort(404, 'サブドメインが見つかりません。');
        }

        return view('admin.subdomain-edit', compact('user', 'subdomain'));
    }

    /**
     * サブドメイン情報を更新
     */
    public function update(UpdateSubdomainRequest $request)
    {
        $user = \App\Models\User::with(['role', 'subdomain'])->find(Auth::id());

        // 権限チェック（レベル80以上）
        if (! $user->hasLevelOrAbove(80)) {
            abort(403, 'この機能にアクセスする権限がありません。');
        }

        // サブドメイン情報取得
        $subdomain = $user->subdomain;

        if (! $subdomain) {
            abort(404, 'サブドメインが見つかりません。');
        }

        // 学年設定を処理
        $grades = $request->input('grades', []);
        // 空文字列を除外
        $grades = array_filter($grades, function ($grade) {
            return ! empty(trim($grade));
        });
        // 配列のインデックスを再設定
        $grades = array_values($grades);

        // サブドメイン情報を更新
        $subdomain->update([
            'system_name' => $request->system_name,
            'description' => $request->description,
            'voucher_amount' => $request->voucher_amount,
            'voucher_expiry' => $request->voucher_expiry,
            'voucher_publish_date' => $request->voucher_publish_date,
            'postal_code' => $request->filled('postal_code') ? $request->postal_code : null,
            'address' => $request->filled('address') ? $request->address : null,
            'phone' => $request->filled('phone') ? $request->phone : null,
            'fax' => $request->filled('fax') ? $request->fax : null,
            'transfer_date_rule' => $request->filled('transfer_date_rule') ? $request->transfer_date_rule : null,
            'zengin_requester_code' => $request->filled('zengin_requester_code') ? $request->zengin_requester_code : null,
            'zengin_requester_name' => $request->filled('zengin_requester_name') ? $request->zengin_requester_name : null,
            'zengin_bank_code' => $request->filled('zengin_bank_code') ? $request->zengin_bank_code : null,
            'zengin_bank_name' => $request->filled('zengin_bank_name') ? $request->zengin_bank_name : null,
            'zengin_branch_code' => $request->filled('zengin_branch_code') ? $request->zengin_branch_code : null,
            'zengin_branch_name' => $request->filled('zengin_branch_name') ? $request->zengin_branch_name : null,
            'zengin_account_type' => $request->filled('zengin_account_type') ? $request->zengin_account_type : null,
            'zengin_account_number' => $request->filled('zengin_account_number') ? $request->zengin_account_number : null,
        ]);

        // 学年設定を保存
        $subdomain->setSetting('grades', $grades);
        $subdomain->save();

        return redirect()->route('admin.subdomain.edit')
            ->with('success', 'サブドメイン情報を更新しました。');
    }
}
