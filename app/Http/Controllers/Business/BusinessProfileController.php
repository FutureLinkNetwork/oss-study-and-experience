<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessEmailUpdateRequest;
use App\Http\Requests\BusinessNotificationUpdateRequest;
use App\Http\Requests\BusinessPasswordUpdateRequest;
use App\Models\BusinessInfo;
use App\Services\BankService;
use App\Services\MailLogService;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class BusinessProfileController extends Controller
{
    use HandlesAuth;

    public function __construct(
        private MailLogService $mailLogService,
        private BankService $bankService
    ) {}

    /**
     * 事業者情報管理画面を表示
     */
    public function show(Request $request)
    {
        $user = Auth::user()->load('role');

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);
        $businessInfo = BusinessInfo::where('user_id', $user->id)->first();

        return view('business.profile.edit', compact('user', 'subdomain', 'businessInfo'));
    }

    /**
     * 登録内容の確認（読み取り専用）
     */
    public function showRegistrationConfirm(Request $request)
    {
        $user = Auth::user()->load('role');

        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);
        $businessInfo = BusinessInfo::where('user_id', $user->id)->first();

        if (! $businessInfo) {
            return redirect()->route('business.profile.edit')
                ->with('error', '事業者情報が見つかりません。');
        }

        $applicantTypeLabel = match ($businessInfo->applicant_type) {
            'corporation' => '法人',
            'voluntary_group' => '任意団体',
            'individual' => '個人事業主',
            'government_agency' => '行政機関',
            default => $businessInfo->applicant_type ?? '—',
        };

        $addressParts = array_filter([
            $businessInfo->prefecture,
            $businessInfo->city,
            $businessInfo->address1,
            $businessInfo->building_name,
        ], fn (?string $part): bool => $part !== null && $part !== '');

        $addressDisplay = implode('', $addressParts);

        $bankNameDisplay = '';
        if ($businessInfo->bank_code) {
            $bankNameDisplay = $this->bankService->getBankName($businessInfo->bank_code)
                ?? '（'.$businessInfo->bank_code.'）';
        }

        $branchNameDisplay = '';
        if ($businessInfo->bank_code && $businessInfo->branch_code) {
            $branchNameDisplay = $this->bankService->getBranchName($businessInfo->bank_code, $businessInfo->branch_code)
                ?? '（'.$businessInfo->branch_code.'）';
        }

        return view('business.profile.registration-confirm', compact(
            'user',
            'subdomain',
            'businessInfo',
            'applicantTypeLabel',
            'addressDisplay',
            'bankNameDisplay',
            'branchNameDisplay'
        ));
    }

    /**
     * メールアドレス変更画面を表示
     */
    public function showEmailEdit(Request $request)
    {
        $user = Auth::user()->load('role');

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('business.profile.edit-email', compact('user', 'subdomain'));
    }

    /**
     * パスワード変更画面を表示
     */
    public function showPasswordEdit(Request $request)
    {
        $user = Auth::user()->load('role');

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('business.profile.edit-password', compact('user', 'subdomain'));
    }

    /**
     * メールアドレス更新処理
     */
    public function updateEmail(BusinessEmailUpdateRequest $request)
    {
        $user = Auth::user()->load('role');

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $oldEmail = $user->email;

        // メールアドレスが変更されている場合のみ更新
        if ($request->email !== $user->email) {
            $user->update(['email' => $request->email, 'login_id' => $request->email]);

            // BusinessInfoモデルのemailも同期して更新
            $businessInfo = BusinessInfo::where('user_id', $user->id)->first();

            if ($businessInfo) {
                $businessInfo->update(['email' => $request->email]);
            }

            /** メール関連処理停止
            // メールアドレス変更の確認メールを送信（ログファイルに保存）
            $subject = 'メールアドレス変更の確認';
            $body = sprintf(
                "%s 様\n\n".
                "習い事クーポン管理システムのメールアドレスが変更されました。\n\n".
                "旧メールアドレス: %s\n".
                "新しいメールアドレス: %s\n".
                "変更日時: %s\n\n".
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                "このメールは自動送信されています。\n".
                "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
                $user->name ?? $user->login_id,
                $oldEmail,
                $request->email,
                now()->format('Y年m月d日 H:i')
            );

            $this->mailLogService->logMail($request->email, $subject, $body);
             */

            return redirect()->route('business.profile.edit.email')
                ->with('success', 'メールアドレスを更新しました。');
        }

        return redirect()->route('business.profile.edit.email')
            ->with('info', 'メールアドレスに変更はありません。');
    }

    /**
     * パスワード更新処理
     */
    public function updatePassword(BusinessPasswordUpdateRequest $request)
    {
        $user = Auth::user()->load('role');

        // 事業者ロールでない場合はログアウト
        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        // 現在のパスワードを確認
        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
        }

        // パスワードを更新
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('business.profile.edit.password')
            ->with('success', 'パスワードを更新しました。');
    }

    /**
     * メール通知設定更新処理
     */
    public function updateNotification(BusinessNotificationUpdateRequest $request)
    {
        $user = Auth::user()->load('role');

        if ($user->role->name !== 'subdomain_business') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('business.login')
                ->with('error', '事業者権限がありません。');
        }

        $businessInfo = BusinessInfo::where('user_id', $user->id)->first();
        if (! $businessInfo) {
            return redirect()->route('business.profile.edit')
                ->with('error', '事業者情報が見つかりません。');
        }

        $businessInfo->update(['email_timing' => $request->email_timing]);

        return redirect()->route('business.profile.edit')
            ->with('success', 'メール通知設定を更新しました。');
    }
}
