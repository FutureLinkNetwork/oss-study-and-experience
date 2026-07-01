<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCouponUsageRequest;
use App\Models\VoucherUsage;
use App\Services\CouponUsageCsvExportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponUsageController extends Controller
{
    /**
     * 編集可能な最小利用日（当月1日の先月1日 00:00:00）
     */
    private function editableUsedAtFrom(): Carbon
    {
        return Carbon::today()->startOfMonth()->subMonth()->startOfDay();
    }

    /**
     * 一覧
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $query = VoucherUsage::query()
            ->where('subdomain_id', $subdomainId)
            ->with(['user.beneficiary', 'classroomInfo', 'businessInfo']);

        if ($request->filled('used_at_from')) {
            $from = Carbon::parse($request->used_at_from)->startOfDay();
            $query->where('used_at', '>=', $from);
        }
        if ($request->filled('used_at_to')) {
            $to = Carbon::parse($request->used_at_to)->endOfDay();
            $query->where('used_at', '<=', $to);
        }
        if ($request->filled('child_name')) {
            $keyword = $request->child_name;
            $query->whereHas('user.beneficiary', function ($q) use ($keyword) {
                $q->where('child_name', 'LIKE', '%'.$keyword.'%');
            });
        }
        if ($request->filled('classroom_name')) {
            $keyword = $request->classroom_name;
            $query->whereHas('classroomInfo', function ($q) use ($keyword) {
                $q->where('classroom_name', 'LIKE', '%'.$keyword.'%');
            });
        }

        $usages = $query->orderByDesc('used_at')->paginate(20)->withQueryString();
        $filters = $request->only(['used_at_from', 'used_at_to', 'child_name', 'classroom_name']);
        $subdomain = $user->subdomain;

        return view('admin.coupon_usages.index', compact('usages', 'filters', 'subdomain'));
    }

    /**
     * CSV出力（検索条件を引き継いで全件出力）
     */
    public function exportCsv(Request $request, CouponUsageCsvExportService $exportService): StreamedResponse|RedirectResponse
    {
        $user = Auth::user();

        return $exportService->downloadResponse($request, $user->subdomain_id);
    }

    /**
     * 詳細
     */
    public function show(Request $request, VoucherUsage $voucherUsage): View|RedirectResponse
    {
        $user = Auth::user();
        if ($voucherUsage->subdomain_id !== $user->subdomain_id) {
            abort(404);
        }

        $voucherUsage->load(['user.beneficiary', 'classroomInfo', 'businessInfo', 'courseInfo']);
        $editableFrom = $this->editableUsedAtFrom();
        $isEditable = ! $voucherUsage->is_cancelled
            && $voucherUsage->used_at
            && Carbon::parse($voucherUsage->used_at)->gte($editableFrom);

        $subdomain = $user->subdomain;

        return view('admin.coupon_usages.show', compact('voucherUsage', 'isEditable', 'editableFrom', 'subdomain'));
    }

    /**
     * 更新
     */
    public function update(UpdateCouponUsageRequest $request, VoucherUsage $voucherUsage): RedirectResponse
    {
        $user = Auth::user();
        if ($voucherUsage->subdomain_id !== $user->subdomain_id) {
            abort(404);
        }

        if ($voucherUsage->is_cancelled) {
            return redirect()->route('admin.coupon-usages.show', $voucherUsage)
                ->with('error', 'キャンセル済みの利用は編集できません。');
        }

        $editableFrom = $this->editableUsedAtFrom();
        if (! $voucherUsage->used_at || Carbon::parse($voucherUsage->used_at)->lt($editableFrom)) {
            return redirect()->route('admin.coupon-usages.show', $voucherUsage)
                ->with('error', '先月分以降のデータのみ編集できます。');
        }

        $voucherUsage->used_at = Carbon::parse($request->validated('used_at'))->startOfDay();
        $voucherUsage->amount = $request->validated('amount');
        $voucherUsage->admin_correction_memo = $request->validated('admin_correction_memo');
        $voucherUsage->admin_corrected_at = now();

        if ($request->boolean('is_cancelled')) {
            $voucherUsage->is_cancelled = true;
            $voucherUsage->cancelled_at = now();
            $voucherUsage->cancelled_by_user_id = $user->id;
        }

        $voucherUsage->save();

        return redirect()->route('admin.coupon-usages.show', $voucherUsage)
            ->with('success', '保存しました。');
    }
}
