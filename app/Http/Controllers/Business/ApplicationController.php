<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateApplicationMemoRequest;
use App\Models\BusinessInfo;
use App\Models\Subdomain;
use App\Models\VoucherUsage;
use App\Traits\HandlesAuth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    use HandlesAuth;

    /**
     * 申込一覧を表示
     */
    public function index(Request $request)
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $query = $this->buildApplicationsQuery($request, $businessInfo, $subdomain)
            ->with(['user', 'classroomInfo', 'courseInfo'])
            ->orderBy('used_at', 'desc');

        $applications = $query->paginate(20)->withQueryString();

        $classrooms = $businessInfo->classrooms()
            ->orderBy('classroom_name')
            ->get();

        $searchYear = $request->input('year');
        $searchMonth = $request->input('month');
        $searchClassroomId = $request->input('classroom_id');
        $searchApplicantName = $request->input('applicant_name');
        $searchCancelled = $request->input('cancelled', 'all');

        return view('business.applications.index', compact(
            'subdomain',
            'businessInfo',
            'applications',
            'classrooms',
            'searchYear',
            'searchMonth',
            'searchClassroomId',
            'searchApplicantName',
            'searchCancelled'
        ));
    }

    /**
     * 申込一覧をCSVでダウンロード
     */
    public function export(Request $request): StreamedResponse
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            abort(403, '事業者情報が見つかりません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $applications = $this->buildApplicationsQuery($request, $businessInfo, $subdomain)
            ->with(['user', 'classroomInfo', 'courseInfo'])
            ->orderBy('used_at', 'desc')
            ->get();

        $filename = 'applications_'.now()->format('YmdHis').'.csv';

        return response()->streamDownload(function () use ($applications) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            $headers = ['申込日', '申込教室', 'コース', '金額', '申込者名', '状態', '事業者メモ'];
            $sjisHeaders = array_map(fn ($h) => mb_convert_encoding($h, 'SJIS-win', 'UTF-8'), $headers);
            fputcsv($output, $sjisHeaders);

            foreach ($applications as $application) {
                $usedAt = $application->used_at ? $application->used_at->format('Y/m/d H:i') : '';
                $classroomName = $application->classroomInfo->classroom_name ?? '不明';
                $courseName = $application->courseInfo
                    ? $application->courseInfo->course_name
                    : '金額指定利用';
                $amount = (string) $application->amount;
                $applicantName = $application->user->name ?? '不明';
                $status = $application->is_cancelled ? 'キャンセル済み' : '申込済み';
                $businessMemo = $application->business_memo ?? '';

                $row = [$usedAt, $classroomName, $courseName, $amount, $applicantName, $status, $businessMemo];
                $sjisRow = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8'), $row);
                fputcsv($output, $sjisRow);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * 申込詳細を表示
     */
    public function show(Request $request, VoucherUsage $application)
    {
        // 権限チェック（自分の事業者の申込みのみアクセス可能）
        if (! $this->canAccessApplication($application)) {
            abort(403, 'この申込みにアクセスする権限がありません。');
        }

        // リレーションを読み込み
        $application->load(['user', 'classroomInfo', 'courseInfo', 'businessInfo']);

        // サブドメインを取得
        $subdomain = $this->getCurrentSubdomain($request);

        return view('business.applications.show', compact('subdomain', 'application'));
    }

    /**
     * 事業者メモを更新
     */
    public function update(UpdateApplicationMemoRequest $request, VoucherUsage $application)
    {
        // 権限チェック
        if (! $this->canAccessApplication($application)) {
            abort(403, 'この申込みを編集する権限がありません。');
        }

        // 事業者メモを更新
        $application->update([
            'business_memo' => $request->input('business_memo'),
        ]);

        return redirect()
            ->route('business.applications.show', $application)
            ->with('success', '事業者メモを更新しました。');
    }

    /**
     * 検索条件に基づく申込クエリを組み立てる
     *
     * @return Builder<VoucherUsage>
     */
    private function buildApplicationsQuery(Request $request, BusinessInfo $businessInfo, Subdomain $subdomain): Builder
    {
        $searchYear = $request->input('year');
        $searchMonth = $request->input('month');
        $searchClassroomId = $request->input('classroom_id');
        $searchApplicantName = $request->input('applicant_name');
        $searchCancelled = $request->input('cancelled', 'all');

        $query = VoucherUsage::query()
            ->where('business_info_id', $businessInfo->id)
            ->where('subdomain_id', $subdomain->id);

        if ($searchYear && $searchMonth) {
            $startDate = Carbon::createFromFormat('Y-m', sprintf('%04d-%02d', $searchYear, $searchMonth))->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', sprintf('%04d-%02d', $searchYear, $searchMonth))->endOfMonth();
            $query->whereBetween('used_at', [$startDate, $endDate]);
        } elseif ($searchYear) {
            $startDate = Carbon::createFromFormat('Y', $searchYear)->startOfYear();
            $endDate = Carbon::createFromFormat('Y', $searchYear)->endOfYear();
            $query->whereBetween('used_at', [$startDate, $endDate]);
        }

        if ($searchClassroomId) {
            $query->where('classroom_info_id', $searchClassroomId);
        }

        if ($searchApplicantName) {
            $query->whereHas('user', function ($q) use ($searchApplicantName) {
                $q->where('name', 'like', '%'.$searchApplicantName.'%');
            });
        }

        if ($searchCancelled === 'only') {
            $query->where('is_cancelled', true);
        } elseif ($searchCancelled === 'exclude') {
            $query->where('is_cancelled', false);
        }

        return $query;
    }

    /**
     * ログインユーザーの事業者情報を取得
     */
    private function getUserBusinessInfo(): ?BusinessInfo
    {
        return BusinessInfo::where('user_id', Auth::id())
            // ->where('is_active', true)
            ->first();
    }

    /**
     * 指定された申込みにアクセス可能かチェック
     */
    private function canAccessApplication(VoucherUsage $application): bool
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return false;
        }

        return $application->business_info_id === $businessInfo->id;
    }
}
