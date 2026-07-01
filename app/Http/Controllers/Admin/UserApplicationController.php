<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserApplicationUpdateRequest;
use App\Models\UserApplication;
use App\Services\SubdomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserApplicationController extends Controller
{
    /**
     * 利用者申請一覧表示
     */
    public function index(Request $request): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // クエリビルダーを開始
        $query = UserApplication::with('subdomain')
            ->where('subdomain_id', $subdomain->id);

        // 絞り込み条件を適用
        if ($request->filled('certification_number')) {
            $query->where('certification_number', 'like', '%'.$request->certification_number.'%');
        }

        if ($request->filled('child_name')) {
            $query->where('child_name', 'like', '%'.$request->child_name.'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('elementary_school_name')) {
            $query->where('elementary_school_name', 'like', '%'.$request->elementary_school_name.'%');
        }

        if ($request->filled('is_exported')) {
            if ($request->is_exported === 'excluded') {
                $query->where('is_excluded_from_download', true);
            } else {
                $isExported = $request->is_exported === '1';
                $query->where('is_exported', $isExported);
            }
        }

        // 申請日の新しい順でソート
        $userApplications = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only([
            'certification_number',
            'child_name',
            'date_from',
            'date_to',
            'elementary_school_name',
            'is_exported',
        ]);

        return view('admin.user-applications.index', compact('userApplications', 'filters'));
    }

    /**
     * 利用者申請詳細表示
     */
    public function show(Request $request, UserApplication $userApplication): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // アクセス権限チェック: 現在表示しているサブドメインのデータのみアクセス可能
        if ($userApplication->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $userApplication->load('subdomain');

        return view('admin.user-applications.show', compact('userApplication'));
    }

    /**
     * 利用者申請の更新（ダウンロード対象外・備考）
     */
    public function update(UserApplicationUpdateRequest $request, UserApplication $userApplication): RedirectResponse
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

        if ($userApplication->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $userApplication->update([
            'is_excluded_from_download' => $request->boolean('is_excluded_from_download'),
            'admin_remarks' => $request->input('admin_remarks'),
        ]);

        return redirect()
            ->route('admin.user-applications.show', $userApplication)
            ->with('success', '保存しました。');
    }

    /**
     * CSV出力
     */
    public function export(Request $request): StreamedResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // クエリビルダーを開始（一覧と同じ絞り込み条件を適用）
        $query = UserApplication::where('subdomain_id', $subdomain->id)
            ->where('is_excluded_from_download', false);

        // 絞り込み条件を適用（検索条件がある場合のみ適用）
        $hasFilters = false;

        if ($request->filled('certification_number')) {
            $query->where('certification_number', 'like', '%'.$request->certification_number.'%');
            $hasFilters = true;
        }

        if ($request->filled('child_name')) {
            $query->where('child_name', 'like', '%'.$request->child_name.'%');
            $hasFilters = true;
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
            $hasFilters = true;
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
            $hasFilters = true;
        }

        if ($request->filled('elementary_school_name')) {
            $query->where('elementary_school_name', 'like', '%'.$request->elementary_school_name.'%');
            $hasFilters = true;
        }

        if ($request->filled('is_exported')) {
            $isExported = $request->is_exported === '1';
            $query->where('is_exported', $isExported);
            $hasFilters = true;
        }

        // 申請日の新しい順でソート
        $userApplications = $query->orderBy('created_at', 'desc')->get();

        // 検索条件が適用されていることをログに記録
        Log::info('User applications CSV export', [
            'user_id' => $user->id,
            'subdomain_id' => $subdomain->id,
            'has_filters' => $hasFilters,
            'filters' => $request->only([
                'certification_number',
                'child_name',
                'date_from',
                'date_to',
                'elementary_school_name',
                'is_exported',
            ]),
            'count' => $userApplications->count(),
        ]);

        // CSVファイル名を生成
        $filename = 'user_applications_'.now()->format('YmdHis').'.csv';

        // 更新対象のIDリストを取得
        $applicationIds = $userApplications->pluck('id')->toArray();

        return response()->streamDownload(function () use ($userApplications, $applicationIds, $user) {
            // 出力ストリームを開く
            $output = fopen('php://output', 'w');

            // CSVヘッダー行を定義
            $headers = [
                'こどもID',
                '就学援助認定番号',
                '就学援助認定者名（保護者名）',
                '就学援助認定者名カナ（保護者名）',
                '就学援助認定者生年月日',
                '住所',
                '電話番号',
                'メールアドレス',
                '対象児童名',
                '対象児童名カナ',
                '対象児童生年月日',
                '小学校名',
                '学年',
                '対象児童の住所',
                '申請者と同一の住所',
                '調査同意',
                '教室名1',
                '所在地1',
                '電話番号1',
                '担当者1',
                '教室名2',
                '所在地2',
                '電話番号2',
                '担当者2',
                '教室名3',
                '所在地3',
                '電話番号3',
                '担当者3',
                '出力済みフラグ',
                '申請日',
                '認定日',
            ];

            // ヘッダー行をShift-JISに変換して出力
            $sjisHeaders = array_map(function ($header) {
                return mb_convert_encoding($header, 'SJIS-win', 'UTF-8');
            }, $headers);
            fputcsv($output, $sjisHeaders);

            // データ行を出力
            foreach ($userApplications as $application) {
                $row = [
                    '',
                    $application->certification_number,
                    $application->guardian_name,
                    $application->guardian_name_kana ?? '',
                    $application->guardian_birth_date ? $application->guardian_birth_date->format('Y-m-d') : '',
                    $application->guardian_address,
                    $application->guardian_phone,
                    $application->guardian_email,
                    $application->child_name,
                    $application->child_name_kana ?? '',
                    $application->child_birth_date ? $application->child_birth_date->format('Y-m-d') : '',
                    $application->elementary_school_name,
                    $application->grade,
                    $application->child_address,
                    $application->child_address_same_as_guardian ? 'はい' : 'いいえ',
                    $application->survey_consent ? 'はい' : 'いいえ',
                    $application->classroom_name_1 ?? '',
                    $application->classroom_location_1 ?? '',
                    $application->classroom_phone_1 ?? '',
                    $application->classroom_contact_person_1 ?? '',
                    $application->classroom_name_2 ?? '',
                    $application->classroom_location_2 ?? '',
                    $application->classroom_phone_2 ?? '',
                    $application->classroom_contact_person_2 ?? '',
                    $application->classroom_name_3 ?? '',
                    $application->classroom_location_3 ?? '',
                    $application->classroom_phone_3 ?? '',
                    $application->classroom_contact_person_3 ?? '',
                    $application->is_exported ? '出力済み' : '未出力',
                    $application->created_at->format('Y-m-d H:i:s'),
                    '',
                ];

                // データ行をShift-JISに変換して出力
                $sjisRow = array_map(function ($cell) {
                    return mb_convert_encoding((string) $cell, 'SJIS-win', 'UTF-8');
                }, $row);
                fputcsv($output, $sjisRow);
            }

            fclose($output);

            // CSV出力完了後にis_exportedフラグを更新
            // ストリーミングコールバック内で直接更新（DB接続が有効なうちに実行）
            try {
                \App\Models\UserApplication::whereIn('id', $applicationIds)
                    ->update(['is_exported' => true]);

                Log::info('User applications exported', [
                    'user_id' => $user->id,
                    'count' => count($applicationIds),
                    'application_ids' => $applicationIds,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update is_exported flag', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'application_ids' => $applicationIds,
                ]);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * 添付ファイルダウンロード
     */
    public function downloadDocument(Request $request, UserApplication $userApplication): StreamedResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'このファイルにアクセスする権限がありません。');
        }

        // 現在表示しているURLのサブドメインを取得
        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // アクセス権限チェック: 現在表示しているサブドメインのデータのみアクセス可能
        if ($userApplication->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        // 添付ファイルの存在確認
        if (empty($userApplication->document_s3_key) || empty($userApplication->document_original_filename)) {
            abort(404, '添付ファイルが見つかりません。');
        }

        try {
            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
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

            // S3からファイルを取得
            try {
                $fileExists = Storage::disk('s3')->exists($userApplication->document_s3_key);
            } catch (\Exception $e) {
                Log::error('S3 existence check failed', [
                    's3_key' => $userApplication->document_s3_key,
                    'error' => $e->getMessage(),
                ]);
                $fileExists = false;
            } finally {
                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
            }

            if (! $fileExists) {
                abort(404, 'ファイルが見つかりません。');
            }

            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
            if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                unset($_ENV['AWS_ACCESS_KEY_ID']);
                putenv('AWS_ACCESS_KEY_ID');
            }
            if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                putenv('AWS_SECRET_ACCESS_KEY');
            }

            try {
                $fileContent = Storage::disk('s3')->get($userApplication->document_s3_key);
            } catch (\Exception $e) {
                Log::error('S3 file get failed', [
                    's3_key' => $userApplication->document_s3_key,
                    'error' => $e->getMessage(),
                ]);
                abort(404, 'ファイルの取得に失敗しました。');
            } finally {
                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
            }

            $mimeType = $userApplication->document_mime_type ?? 'application/octet-stream';

            // ログ記録
            Log::info('User application document downloaded', [
                'user_application_id' => $userApplication->id,
                'user_id' => $user->id,
                'filename' => $userApplication->document_original_filename,
                'file_size' => $userApplication->document_file_size,
            ]);

            // ファイルをダウンロード
            return response()->streamDownload(
                function () use ($fileContent) {
                    echo $fileContent;
                },
                $userApplication->document_original_filename,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]
            );
        } catch (\Exception $e) {
            Log::error('User application document download failed', [
                'user_application_id' => $userApplication->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }
}
