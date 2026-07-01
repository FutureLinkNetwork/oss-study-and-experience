<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CourseInfo;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Services\ImmediateCouponAppliedNotificationService;
use App\Support\UserCouponBalanceCalculator;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherApplicationController extends Controller
{
    use HandlesAuth;

    public function __construct(
        protected ImmediateCouponAppliedNotificationService $immediateCouponNotificationService
    ) {}

    /**
     * 申込一覧を表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('User\VoucherApplicationController::index - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        // ログインユーザーの申込を取得（キャンセル済みも含む）
        $applications = VoucherUsage::where('user_id', $user->id)
            ->with(['classroomInfo', 'courseInfo'])
            ->orderBy('used_at', 'desc')
            ->paginate(20);

        return view('user.applications.index', compact('subdomain', 'applications'));
    }

    /**
     * 申込詳細を表示
     */
    public function show(Request $request, VoucherUsage $voucherUsage)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // 自分の申込のみアクセス可能
        if ($voucherUsage->user_id !== $user->id) {
            abort(403, 'この申込を閲覧する権限がありません。');
        }

        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('User\VoucherApplicationController::show - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        // リレーションをeager load
        $voucherUsage->load(['classroomInfo', 'courseInfo']);

        // 再度申し込み可能かチェック
        $canReapply = false;
        if ($voucherUsage->courseInfo) {
            // コースがある場合：コースが有効かつクーポン残高が十分かチェック
            $course = $voucherUsage->courseInfo;
            $isCourseValid = $this->isCourseValid($course);
            if ($isCourseValid) {
                $availableBalance = $this->calculateAvailableBalance($user);
                $usageAmount = $this->calculateUsageAmount($course, $subdomain);
                $canReapply = $availableBalance >= $usageAmount;
            }
        } else {
            // コースがない場合（金額指定利用）：クーポン残高が1円以上あれば再度申し込み可能
            $availableBalance = $this->calculateAvailableBalance($user);
            $canReapply = $availableBalance >= 1;
        }

        return view('user.applications.show', compact('subdomain', 'voucherUsage', 'canReapply'));
    }

    /**
     * 申込をキャンセル
     */
    public function cancel(Request $request, VoucherUsage $voucherUsage)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // 自分の申込のみキャンセル可能
        if ($voucherUsage->user_id !== $user->id) {
            abort(403, 'この申込をキャンセルする権限がありません。');
        }

        // 既にキャンセル済みかチェック
        if ($voucherUsage->is_cancelled) {
            return redirect()->route('user.applications.index')
                ->with('error', 'この申込は既にキャンセルされています。');
        }

        // 24時間以内かチェック（used_atから24時間以内）
        $hoursSinceUsed = now()->diffInHours($voucherUsage->used_at);
        if ($hoursSinceUsed >= 24) {
            return redirect()->route('user.applications.index')
                ->with('error', '申込から24時間を経過しているため、キャンセルできません。');
        }

        // QRフラグチェック（QRコードが使用されている場合はキャンセル不可）
        if ($voucherUsage->qr_flag) {
            return redirect()->route('user.applications.index')
                ->with('error', 'QRコードが使用されているため、キャンセルできません。');
        }

        // キャンセル処理
        try {
            DB::transaction(function () use ($voucherUsage, $user) {
                $voucherUsage->update([
                    'is_cancelled' => true,
                    'cancelled_by_user_id' => $user->id,
                    'cancelled_at' => now(),
                ]);
            });

            try {
                $voucherUsage->load(['businessInfo.subdomain']);
                $business = $voucherUsage->businessInfo;
                $subdomain = $business?->subdomain;
                if ($business && $subdomain) {
                    $this->immediateCouponNotificationService->sendCancellationIfImmediate($business, $subdomain, $voucherUsage);
                }
            } catch (\Throwable $e) {
                Log::warning('User\VoucherApplicationController::cancel - 都度キャンセル通知メール送信で例外（キャンセルは成功）', [
                    'voucher_usage_id' => $voucherUsage->id,
                    'exception' => $e->getMessage(),
                ]);
            }

            $redirectRoute = $request->has('from_detail')
                ? route('user.applications.index')
                : route('user.applications.index');

            return redirect($redirectRoute)
                ->with('success', '申込をキャンセルしました。');
        } catch (\Exception $e) {
            Log::error('User\VoucherApplicationController::cancel - Error', [
                'error' => $e->getMessage(),
                'voucher_usage_id' => $voucherUsage->id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('user.applications.index')
                ->with('error', 'キャンセル処理中にエラーが発生しました。もう一度お試しください。');
        }
    }

    /**
     * コースが有効かどうかをチェック
     */
    private function isCourseValid(CourseInfo $course): bool
    {
        // is_activeがfalseの場合は無効
        if (! $course->is_active) {
            return false;
        }

        // 期間判定
        $currentDate = Carbon::now();

        // 両方null：期間制限なし
        if (is_null($course->open_date) && is_null($course->end_date)) {
            return true;
        }

        // open_dateのみnull：end_dateが未来
        if (is_null($course->open_date) && ! is_null($course->end_date)) {
            return $course->end_date >= $currentDate;
        }

        // end_dateのみnull：open_dateが過去
        if (! is_null($course->open_date) && is_null($course->end_date)) {
            return $course->open_date <= $currentDate;
        }

        // 両方設定：現在日付が期間内
        if (! is_null($course->open_date) && ! is_null($course->end_date)) {
            return $course->open_date <= $currentDate && $course->end_date >= $currentDate;
        }

        return false;
    }

    /**
     * クーポン利用可能金額を計算
     */
    private function calculateAvailableBalance(User $user): int
    {
        $subdomain = Subdomain::query()->find($user->subdomain_id);
        if (! $subdomain) {
            return 0;
        }

        return UserCouponBalanceCalculator::calculate($user)['balance'];
    }

    /**
     * コースの利用料金を計算
     */
    private function calculateUsageAmount(CourseInfo $course, Subdomain $subdomain): int
    {
        $basePrice = $course->price;

        // 外税の場合は消費税を追加
        if ($course->tax_type === 'exclusive') {
            $taxRate = $subdomain->tax_rate ?? 10.0;
            $basePrice = (int) round($basePrice * (1 + $taxRate / 100));
        }

        return $basePrice;
    }
}
