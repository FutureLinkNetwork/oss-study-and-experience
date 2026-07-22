<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BeneficiaryImportRequest;
use App\Http\Requests\Admin\BeneficiaryUpdateRequest;
use App\Http\Requests\Admin\SendBulkLoginInfoRequest;
use App\Models\Beneficiary;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\BeneficiaryCsvExportService;
use App\Services\MailLogService;
use App\Services\PdfTemplateService;
use App\Services\SubdomainService;
use App\Services\VoucherIssueService;
use App\Support\FiscalYear;
use App\Support\UserCouponBalanceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected MailLogService $mailLogService,
        protected PdfTemplateService $pdfTemplateService,
        protected BeneficiaryCsvExportService $beneficiaryCsvExportService,
        protected VoucherIssueService $voucherIssueService
    ) {}

    /**
     * 利用者一覧表示
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

        $query = $this->buildIndexQuery($request, $subdomain);
        $beneficiaries = $query->orderBy('beneficiaries.id', 'desc')->paginate(20);

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only([
            'child_id',
            'certification_number',
            'guardian_name',
            'child_name',
            'status',
            'labels',
        ]);

        // 検索結果が存在するかどうか
        $hasResults = $beneficiaries->count() > 0;

        // ステータスが「決定通知書未送信」で検索しているかどうか
        $isStatusNotSentSearch = $request->filled('status') && $request->status === '決定通知書未送信';

        // 現在のサブドメインに「決定通知書送信待ち」が1件でもあるか（メール送信処理中表示用）
        $hasPendingBulkSend = Beneficiary::where('subdomain_id', $subdomain->id)
            ->where('status', '決定通知書送信待ち')
            ->exists();

        return view('admin.beneficiaries.index', compact('beneficiaries', 'filters', 'hasResults', 'isStatusNotSentSearch', 'hasPendingBulkSend'));
    }

    /**
     * 利用者一覧の絞り込みクエリを組み立てる（index・exportで共通）
     *
     * @param  \App\Models\Subdomain  $subdomain
     * @return \Illuminate\Database\Eloquent\Builder<Beneficiary>
     */
    private function buildIndexQuery(Request $request, $subdomain): \Illuminate\Database\Eloquent\Builder
    {
        $query = Beneficiary::with('subdomain', 'user')
            ->where('subdomain_id', $subdomain->id);

        if ($request->filled('child_id')) {
            $query->where('child_id', '=', $request->child_id);
        }
        if ($request->filled('certification_number')) {
            $query->where('certification_number', '=', $request->certification_number);
        }
        if ($request->filled('guardian_name')) {
            $query->where('guardian_name', 'like', '%'.$request->guardian_name.'%');
        }
        if ($request->filled('child_name')) {
            $query->where('child_name', 'like', '%'.$request->child_name.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('labels') && is_array($request->labels)) {
            foreach ($request->labels as $label) {
                if (! empty($label)) {
                    $query->where('labels', 'like', '%'.$label.'%');
                }
            }
        }

        return $query;
    }

    /**
     * 利用者一覧をCSV出力（検索絞り込み条件を反映）
     */
    public function export(Request $request): StreamedResponse
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

        $query = $this->buildIndexQuery($request, $subdomain);
        $filename = 'beneficiaries_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            $this->beneficiaryCsvExportService->streamCsvTo($stream, $query, Carbon::now());
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * 利用者詳細表示
     */
    public function show(Request $request, Beneficiary $beneficiary): View
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
        if ($beneficiary->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        $today = Carbon::today();
        $fiscalYearStartYear = FiscalYear::currentStartYear($today);
        $fiscalYearStart = FiscalYear::startDateFor($fiscalYearStartYear);
        $fiscalYearEnd = FiscalYear::endDateFor($fiscalYearStartYear);

        $beneficiary->load('subdomain', 'user', 'vouchers');

        $balance = UserCouponBalanceCalculator::calculateForBeneficiary($beneficiary, $today)['balance'];

        // 申込履歴（全ての履歴、キャンセル済みも含む）
        $applications = [];
        if ($beneficiary->user_id) {
            $applications = VoucherUsage::where('user_id', $beneficiary->user_id)
                ->with(['classroomInfo', 'courseInfo', 'businessInfo'])
                ->orderBy('used_at', 'desc')
                ->get();
        }

        return view('admin.beneficiaries.show', compact('beneficiary', 'balance', 'applications', 'fiscalYearStart', 'fiscalYearEnd'));
    }

    /**
     * 利用者情報更新
     */
    public function update(BeneficiaryUpdateRequest $request, Beneficiary $beneficiary): RedirectResponse
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
        if ($beneficiary->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        try {
            $data = $request->validated();

            // 申請者と同一の住所がチェックされている場合、child_addressにguardian_addressを設定
            if (isset($data['child_address_same_as_guardian']) && $data['child_address_same_as_guardian']) {
                $data['child_address'] = $data['guardian_address'];
            }

            // ラベルをカンマ区切りの文字列に変換
            if (isset($data['labels']) && is_array($data['labels'])) {
                $data['labels'] = implode(',', array_filter($data['labels']));
            } else {
                $data['labels'] = null;
            }

            // ステータスが「資格喪失」の場合のみdisqualification_dateを設定（未設定の場合）
            if (isset($data['status']) && $data['status'] === '資格喪失' && empty($data['disqualification_date'])) {
                $data['disqualification_date'] = now()->format('Y-m-d');
            }

            $beneficiary->update($data);

            Log::info('Beneficiary updated', [
                'beneficiary_id' => $beneficiary->id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('success', '利用者情報を更新しました。');
        } catch (\Exception $e) {
            Log::error('Beneficiary update failed', [
                'beneficiary_id' => $beneficiary->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', '利用者情報の更新に失敗しました。');
        }
    }

    /**
     * CSV取り込み
     */
    public function import(BeneficiaryImportRequest $request): RedirectResponse
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

        $file = $request->file('csv_file');
        $errors = [];
        $lineNumber = 0;

        try {
            // Shift-JISでCSVファイルを読み込み
            $handle = fopen($file->getRealPath(), 'r');
            $encoding = mb_detect_encoding(file_get_contents($file->getRealPath()), ['SJIS-win', 'UTF-8', 'EUC-JP'], true);

            if ($encoding !== 'UTF-8') {
                // 一時ファイルにUTF-8変換した内容を書き込む
                $tempFile = tmpfile();
                $tempPath = stream_get_meta_data($tempFile)['uri'];
                $content = file_get_contents($file->getRealPath());
                $utf8Content = mb_convert_encoding($content, 'UTF-8', $encoding);
                file_put_contents($tempPath, $utf8Content);
                $handle = fopen($tempPath, 'r');
            }

            // ヘッダー行を読み込み（スキップ）
            $header = fgetcsv($handle);
            $lineNumber++;

            // 期待されるヘッダー
            $expectedHeaders = [
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

            // CSVファイル内のすべての行を読み込み、こどもIDの重複チェック
            $csvRows = [];
            $csvChildIds = [];
            $tempLineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $tempLineNumber++;

                // 空行をスキップ
                if (empty(array_filter($row))) {
                    continue;
                }

                // 列数チェック
                if (count($row) < count($expectedHeaders)) {
                    fclose($handle);

                    return redirect()->route('admin.beneficiaries.index')
                        ->with('error', "CSV取り込みに失敗しました。{$tempLineNumber}行目: 列数が不足しています。");
                }

                // こどもID
                $childId = trim($row[0] ?? '');

                // 必須項目チェック（こどもID）
                if (empty($childId)) {
                    fclose($handle);

                    return redirect()->route('admin.beneficiaries.index')
                        ->with('error', "CSV取り込みに失敗しました。{$tempLineNumber}行目: こどもIDが空です。");
                }

                // こどもIDが空でない場合、CSVファイル内での重複チェック
                if ($childId !== '') {
                    if (isset($csvChildIds[$childId])) {
                        fclose($handle);

                        return redirect()->route('admin.beneficiaries.index')
                            ->with('error', "CSV取り込みに失敗しました。こどもID「{$childId}」が{$csvChildIds[$childId]}行目と{$tempLineNumber}行目で重複しています。");
                    }
                    $csvChildIds[$childId] = $tempLineNumber;
                }

                $csvRows[] = [
                    'row' => $row,
                    'line_number' => $tempLineNumber,
                ];
            }

            // こどもIDのデータベース内での重複チェック（同じサブドメイン内、空でないもののみ）
            $childIdsToCheck = array_keys($csvChildIds);
            if (! empty($childIdsToCheck)) {
                $existingChildIds = Beneficiary::where('subdomain_id', $subdomain->id)
                    ->whereIn('child_id', $childIdsToCheck)
                    ->whereNotNull('child_id')
                    ->pluck('child_id')
                    ->toArray();

                if (! empty($existingChildIds)) {
                    fclose($handle);

                    return redirect()->route('admin.beneficiaries.index')
                        ->with('error', 'CSV取り込みに失敗しました。以下のこどもIDは既に登録されています: '.implode(', ', $existingChildIds));
                }
            }

            // ファイルを再度開く（データ登録用）
            fclose($handle);
            $handle = fopen($file->getRealPath(), 'r');
            $encoding = mb_detect_encoding(file_get_contents($file->getRealPath()), ['SJIS-win', 'UTF-8', 'EUC-JP'], true);

            if ($encoding !== 'UTF-8') {
                $tempFile = tmpfile();
                $tempPath = stream_get_meta_data($tempFile)['uri'];
                $content = file_get_contents($file->getRealPath());
                $utf8Content = mb_convert_encoding($content, 'UTF-8', $encoding);
                file_put_contents($tempPath, $utf8Content);
                $handle = fopen($tempPath, 'r');
            }

            // ヘッダー行をスキップ
            fgetcsv($handle);

            DB::beginTransaction();

            // データ登録処理
            foreach ($csvRows as $csvRowData) {
                $row = $csvRowData['row'];
                $lineNumber = $csvRowData['line_number'];

                try {
                    // データをマッピング
                    $data = [
                        'subdomain_id' => $subdomain->id,
                        'child_id' => trim($row[0] ?? '') ?: null,
                        'certification_number' => trim($row[1] ?? ''),
                        'guardian_name' => trim($row[2] ?? ''),
                        'guardian_name_kana' => trim($row[3] ?? '') ?: null,
                        'guardian_birth_date' => ! empty($row[4]) ? date('Y-m-d', strtotime($row[4])) : null,
                        'guardian_address' => trim($row[5] ?? ''),
                        'guardian_phone' => trim($row[6] ?? ''),
                        'guardian_email' => trim($row[7] ?? ''),
                        'child_name' => trim($row[8] ?? ''),
                        'child_name_kana' => trim($row[9] ?? '') ?: null,
                        'child_birth_date' => ! empty($row[10]) ? date('Y-m-d', strtotime($row[10])) : null,
                        'elementary_school_name' => trim($row[11] ?? ''),
                        'grade' => trim($row[12] ?? ''),
                        'child_address' => trim($row[13] ?? ''),
                        'child_address_same_as_guardian' => ! empty($row[14]) && in_array(strtolower(trim($row[14])), ['はい', 'yes', '1', 'true'], true),
                        'survey_consent' => ! empty($row[15]) && in_array(strtolower(trim($row[15])), ['はい', 'yes', '1', 'true'], true),
                        'classroom_name_1' => trim($row[16] ?? '') ?: null,
                        'classroom_location_1' => trim($row[17] ?? '') ?: null,
                        'classroom_phone_1' => trim($row[18] ?? '') ?: null,
                        'classroom_contact_person_1' => trim($row[19] ?? '') ?: null,
                        'classroom_name_2' => trim($row[20] ?? '') ?: null,
                        'classroom_location_2' => trim($row[21] ?? '') ?: null,
                        'classroom_phone_2' => trim($row[22] ?? '') ?: null,
                        'classroom_contact_person_2' => trim($row[23] ?? '') ?: null,
                        'classroom_name_3' => trim($row[24] ?? '') ?: null,
                        'classroom_location_3' => trim($row[25] ?? '') ?: null,
                        'classroom_phone_3' => trim($row[26] ?? '') ?: null,
                        'classroom_contact_person_3' => trim($row[27] ?? '') ?: null,
                        'application_date' => ! empty($row[29]) ? date('Y-m-d', strtotime($row[29])) : now()->format('Y-m-d'),
                        'certification_date' => ! empty($row[30]) ? date('Y-m-d', strtotime($row[30])) : null,
                        'status' => '決定通知書未送信',
                        'disqualification_date' => null,
                        'labels' => null,
                    ];

                    // 必須項目のチェック
                    if (empty($data['child_id']) || empty($data['guardian_name']) || empty($data['child_name']) || empty($data['certification_date'])) {
                        $errors[] = "{$lineNumber}行目: 必須項目が不足しています。";
                        DB::rollBack();
                        fclose($handle);

                        return redirect()->route('admin.beneficiaries.index')
                            ->with('error', 'CSV取り込みに失敗しました。'.implode("\n", $errors));
                    }

                    Beneficiary::create($data);
                } catch (\Exception $e) {
                    $errors[] = "{$lineNumber}行目: {$e->getMessage()}";
                    DB::rollBack();
                    fclose($handle);

                    return redirect()->route('admin.beneficiaries.index')
                        ->with('error', 'CSV取り込みに失敗しました。'.implode("\n", $errors));
                }
            }

            DB::commit();
            fclose($handle);

            Log::info('Beneficiaries imported', [
                'user_id' => $user->id,
                'subdomain_id' => $subdomain->id,
                'line_count' => $lineNumber - 1,
            ]);

            return redirect()->route('admin.beneficiaries.index')
                ->with('success', 'CSV取り込みが完了しました。'.($lineNumber - 1).'件のデータを取り込みました。');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV import failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.beneficiaries.index')
                ->with('error', 'CSV取り込みに失敗しました。'.$e->getMessage());
        }
    }

    /**
     * 利用者にログイン情報を送信（単一）
     */
    public function sendLoginInfo(Request $request, Beneficiary $beneficiary): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です。',
            ], 403);
        }

        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 40) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限がありません。権限レベルが不足しています。',
            ], 403);
        }

        try {
            $subdomainService = new SubdomainService;
            $subdomain = $subdomainService->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'サブドメインを取得できませんでした。',
            ], 404);
        }

        if ($beneficiary->subdomain_id !== $subdomain->id) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限がありません。',
            ], 403);
        }

        if (empty($beneficiary->guardian_email) || ! filter_var($beneficiary->guardian_email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => '保護者メールアドレスが登録されていないか、無効です。',
            ], 400);
        }

        $loginUrl = $request->getSchemeAndHttpHost().'/login';
        $subject = 'ログイン情報のお知らせ';
        $bodyTemplate = "%s 様\n\n".
            "習い事クーポン管理システムのログイン情報をお知らせいたします。\n\n".
            "添付されている交付決定通知書に記載の注意事項等をご確認の上、ご利用ください。\n".
            "なお、本メールはクーポンの交付決定のご連絡となります。習い事の申し込み状況等は、事前に各教室へ直接ご連絡ください。\n\n".
            "ログインID: %s\n".
            "パスワード: %s\n\n".
            "ログインURL: %s\n\n".
            "初回ログイン後、パスワードの変更をお願いいたします。\n\n".
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
            "このメールは自動送信されています。\n".
            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        if (! empty($beneficiary->user_id)) {
            $userModel = User::find($beneficiary->user_id);
            if (! $userModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'ユーザーが見つかりません。',
                ], 404);
            }
            $password = Str::random(10);
            $userModel->update([
                'password' => Hash::make($password),
                'last_login_at' => null,
            ]);
            $body = sprintf(
                $bodyTemplate,
                $beneficiary->guardian_name,
                $beneficiary->child_id,
                $password,
                $loginUrl
            );
            Mail::raw($body, function ($message) use ($beneficiary, $subject) {
                $message->to($beneficiary->guardian_email)
                    ->from('info@www.study-and-experience.jp')
                    ->subject($subject);
            });
            $this->mailLogService->logMail($beneficiary->guardian_email, $subject, $body);
            Log::info('利用者ログイン情報を再送信', [
                'beneficiary_id' => $beneficiary->id,
                'child_id' => $beneficiary->child_id,
                'email' => $beneficiary->guardian_email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ログイン情報を送信しました。',
            ]);
        }

        if (empty($beneficiary->child_id)) {
            return response()->json([
                'success' => false,
                'message' => 'こどもIDが登録されていません。',
            ], 400);
        }

        $existingUser = User::where('subdomain_id', $subdomain->id)
            ->where('login_id', $beneficiary->child_id)
            ->first();
        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'このこどもIDは既に使用されています。',
            ], 400);
        }

        $subdomainUserRole = Role::where('name', 'subdomain_user')->first();
        if (! $subdomainUserRole) {
            return response()->json([
                'success' => false,
                'message' => 'subdomain_userロールが見つかりません。',
            ], 500);
        }

        $password = Str::random(10);
        $newUser = User::create([
            'subdomain_id' => $subdomain->id,
            'login_id' => $beneficiary->child_id,
            'password' => Hash::make($password),
            'name' => $beneficiary->child_name,
            'display_name' => $beneficiary->child_name,
            'email' => $beneficiary->guardian_email,
            'role_id' => $subdomainUserRole->id,
            'is_active' => true,
        ]);
        $beneficiary->update(['user_id' => $newUser->id]);

        $body = sprintf(
            $bodyTemplate,
            $beneficiary->guardian_name,
            $beneficiary->child_id,
            $password,
            $loginUrl
        );
        Mail::raw($body, function ($message) use ($beneficiary, $subject) {
            $message->to($beneficiary->guardian_email)
                ->from('info@www.study-and-experience.jp')
                ->subject($subject);
        });
        $this->mailLogService->logMail($beneficiary->guardian_email, $subject, $body);
        Log::info('利用者ログイン情報を新規作成して送信', [
            'beneficiary_id' => $beneficiary->id,
            'child_id' => $beneficiary->child_id,
            'email' => $beneficiary->guardian_email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ログイン情報を送信しました。',
        ]);
    }

    /**
     * メール一括送信
     */
    public function sendBulkLoginInfo(SendBulkLoginInfoRequest $request): RedirectResponse
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

        $shouldIssueVoucher = $request->shouldIssueVoucher();

        if ($shouldIssueVoucher && ($subdomain->voucher_amount === null || $subdomain->voucher_expiry === null)) {
            return redirect()->route('admin.beneficiaries.index', $request->only([
                'child_id',
                'certification_number',
                'guardian_name',
                'child_name',
                'status',
                'labels',
            ]))->with('error', 'サブドメインのクーポン設定が完了していません。クーポン付与を外すか、設定を完了してください。');
        }

        // 一覧と同じ検索条件を適用し、対象は常に「決定通知書未送信」に限定
        $request->merge(['status' => '決定通知書未送信']);
        $beneficiaries = $this->buildIndexQuery($request, $subdomain)->get();

        if ($beneficiaries->isEmpty()) {
            return redirect()->route('admin.beneficiaries.index')
                ->with('error', 'メール送信対象の利用者がありません。');
        }

        $count = $beneficiaries->count();
        foreach ($beneficiaries as $beneficiary) {
            $beneficiary->update([
                'status' => '決定通知書送信待ち',
                'pending_voucher_issue' => $shouldIssueVoucher,
            ]);
        }

        $message = "{$count}件をメール送信待ちに登録しました。5分以内にバッチで送信されます。";
        if ($shouldIssueVoucher) {
            $message .= '送信成功時にクーポンを付与します。';
        }

        return redirect()->route('admin.beneficiaries.index')
            ->with('success', $message);
    }

    /**
     * クーポン付与
     */
    public function issueVoucher(Request $request, Beneficiary $beneficiary): RedirectResponse
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
        if ($beneficiary->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        // ステータスが「資格喪失」の場合はエラー
        if ($beneficiary->status === '資格喪失') {
            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', '資格喪失の利用者にはクーポンを付与できません。');
        }

        // サブドメインのvoucher_amountとvoucher_expiryが設定されているかチェック
        if ($subdomain->voucher_amount === null || $subdomain->voucher_expiry === null) {
            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', 'サブドメインのクーポン設定が完了していません。');
        }

        try {
            DB::transaction(function () use ($beneficiary, $subdomain) {
                $this->voucherIssueService->issueForBeneficiary($beneficiary, $subdomain);
            });

            Log::info('Voucher issued manually', [
                'beneficiary_id' => $beneficiary->id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('success', 'クーポンを付与しました。');
        } catch (\Exception $e) {
            Log::error('Voucher issue failed', [
                'beneficiary_id' => $beneficiary->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', 'クーポンの付与に失敗しました。'.$e->getMessage());
        }
    }

    /**
     * クーポン無効化
     */
    public function expireVoucher(Request $request, Beneficiary $beneficiary, Voucher $voucher): RedirectResponse
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
        if ($beneficiary->subdomain_id !== $subdomain->id) {
            abort(403, 'アクセス権限がありません。');
        }

        // クーポンが該当利用者のものかチェック
        if ($voucher->beneficiary_id !== $beneficiary->id) {
            abort(403, 'このクーポンは該当利用者のものではありません。');
        }

        // クーポンが未使用状態かチェック
        if ($voucher->status !== 'unused') {
            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', '未使用のクーポンのみ無効化できます。');
        }

        try {
            $voucher->update(['status' => 'expired']);

            Log::info('Voucher expired manually', [
                'voucher_id' => $voucher->id,
                'beneficiary_id' => $beneficiary->id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('success', 'クーポンを無効化しました。');
        } catch (\Exception $e) {
            Log::error('Voucher expire failed', [
                'voucher_id' => $voucher->id,
                'beneficiary_id' => $beneficiary->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.beneficiaries.show', $beneficiary)
                ->with('error', 'クーポンの無効化に失敗しました。'.$e->getMessage());
        }
    }
}
