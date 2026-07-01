<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminDownload;
use App\Services\SubdomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDownloadController extends Controller
{
    private const ALLOWED_DOWNLOAD_TYPES = ['user_application', 'beneficiary', 'contact', 'inquiry'];

    /**
     * ダウンロード管理一覧（出力日・出力種別で検索）
     */
    public function index(Request $request): View
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }
        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        $query = AdminDownload::query()
            ->forSubdomain($subdomain->id)
            ->with('subdomain')
            ->orderBy('exported_at', 'desc');

        if ($request->filled('exported_at_from')) {
            $query->whereDate('exported_at', '>=', $request->exported_at_from);
        }
        if ($request->filled('exported_at_to')) {
            $query->whereDate('exported_at', '<=', $request->exported_at_to);
        }
        if ($request->filled('download_type') && in_array($request->download_type, self::ALLOWED_DOWNLOAD_TYPES, true)) {
            $query->where('download_type', $request->download_type);
        }

        $downloads = $query->paginate(20)->withQueryString();
        $filters = $request->only(['exported_at_from', 'exported_at_to', 'download_type']);

        return view('admin.downloads.index', compact('downloads', 'filters'));
    }

    /**
     * S3からCSVを取得してダウンロード
     */
    public function download(Request $request, AdminDownload $adminDownload): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }
        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }
        if ($adminDownload->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $s3Key = $adminDownload->s3_key;
        $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        if (($_ENV['AWS_ACCESS_KEY_ID'] ?? null) === 'null' || ($_ENV['AWS_ACCESS_KEY_ID'] ?? null) === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if (($_ENV['AWS_SECRET_ACCESS_KEY'] ?? null) === 'null' || ($_ENV['AWS_SECRET_ACCESS_KEY'] ?? null) === '"null"') {
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
            if (! Storage::disk('s3')->exists($s3Key)) {
                return redirect()->route('admin.downloads.index')
                    ->with('error', 'ファイルが見つかりません。');
            }
            $content = Storage::disk('s3')->get($s3Key);
        } catch (\Throwable $e) {
            Log::error('ダウンロード管理 S3取得エラー', [
                's3_key' => $s3Key,
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('admin.downloads.index')
                ->with('error', 'ダウンロードに失敗しました。');
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

        $filename = basename($adminDownload->s3_key);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
