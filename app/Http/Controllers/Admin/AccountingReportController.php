<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingReportDownload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingReportController extends Controller
{
    /**
     * 指定月のCSVをダウンロードし、ダウンロード追跡を更新
     *
     * @return \Illuminate\Http\RedirectResponse|StreamedResponse
     */
    public function downloadCsv(Request $request)
    {
        return $this->downloadFile($request, 'csv', 'application/csv', 'csv');
    }

    /**
     * 指定月のPDFをダウンロードし、ダウンロード追跡を更新
     *
     * @return \Illuminate\Http\RedirectResponse|StreamedResponse
     */
    public function downloadPdf(Request $request)
    {
        return $this->downloadFile($request, 'pdf', 'application/pdf', 'pdf');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|StreamedResponse
     */
    private function downloadFile(
        Request $request,
        string $fileType,
        string $contentType,
        string $extension
    ) {
        $user = Auth::user();
        $subdomainId = $user->subdomain_id;
        if (! $subdomainId) {
            return redirect()->route('admin.dashboard')->with('error', 'サブドメインが設定されていません。');
        }

        $month = $request->get('month');
        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->route('admin.payments.index')->with('error', '申込月を指定してください。');
        }

        $category = AccountingReportDownload::normalizeCategory($request->get('category'));
        $columns = AccountingReportDownload::columnMapForCategory($category, $fileType);

        $targetMonthDate = $month.'-01';
        $record = AccountingReportDownload::query()
            ->forSubdomain($subdomainId)
            ->whereDate('target_month', $targetMonthDate)
            ->first();

        $s3KeyColumn = $columns['s3_key'];
        $downloadedAtColumn = $columns['downloaded_at'];
        $downloadedByUserIdColumn = $columns['downloaded_by_user_id'];

        $s3Key = $record?->{$s3KeyColumn};
        if (! $record || empty($s3Key)) {
            $typeLabel = $fileType === 'csv' ? 'CSV' : 'PDF';
            $categoryLabel = $category === AccountingReportDownload::CATEGORY_NON_TARGET ? '（公金振替対象外）' : '';

            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', "この月の{$typeLabel}{$categoryLabel}はまだ生成されていません。");
        }

        $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }
        if (getenv('AWS_ACCESS_KEY_ID') === 'null' || getenv('AWS_ACCESS_KEY_ID') === '"null"') {
            putenv('AWS_ACCESS_KEY_ID');
        }
        if (getenv('AWS_SECRET_ACCESS_KEY') === 'null' || getenv('AWS_SECRET_ACCESS_KEY') === '"null"') {
            putenv('AWS_SECRET_ACCESS_KEY');
        }

        try {
            $fileExists = Storage::disk('s3')->exists($s3Key);
            if (! $fileExists) {
                return redirect()->route('admin.payments.index', ['month' => $month])->with('error', 'ファイルが見つかりません。');
            }
            $content = Storage::disk('s3')->get($s3Key);
        } catch (\Throwable $e) {
            Log::error('会計用レポートS3取得エラー', [
                's3_key' => $s3Key,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('admin.payments.index', ['month' => $month])->with('error', 'ダウンロードに失敗しました。');
        } finally {
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }

        $record->update([
            $downloadedAtColumn => now(),
            $downloadedByUserIdColumn => $user->id,
        ]);

        $label = Carbon::parse($month.'-01')->format('Y年n月');
        $suffix = $category === AccountingReportDownload::CATEGORY_NON_TARGET ? '_公金振替対象外' : '';
        $filename = "会計用月次レポート_{$label}{$suffix}.{$extension}";

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $filename,
            ['Content-Type' => $contentType]
        );
    }
}
