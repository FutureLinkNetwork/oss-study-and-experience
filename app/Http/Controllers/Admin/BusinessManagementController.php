<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseInfo;
use App\Models\Role;
use App\Models\Subdomain;
use App\Models\User;
use App\Services\BusinessCsvExportService;
use App\Services\ImageProcessingService;
use App\Services\MailLogService;
use App\Services\S3KeyPrefix;
use App\Services\SubdomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessManagementController extends Controller
{
    protected readonly ImageProcessingService $imageProcessingService;

    protected readonly MailLogService $mailLogService;

    public function __construct(ImageProcessingService $imageProcessingService, MailLogService $mailLogService)
    {
        $this->imageProcessingService = $imageProcessingService;
        $this->mailLogService = $mailLogService;
    }

    /**
     * 事業者管理画面のメイン表示
     */
    public function index(Request $request)
    {
        // クエリビルダーを開始
        $query = BusinessInfo::with([
            // 一覧表示では「IDが一番若い（=最小ID）」教室名だけが必要
            'classrooms' => function ($query) {
                return $query->orderBy('id', 'asc')
                    ->limit(1)
                    ->select(['id', 'business_info_id', 'classroom_name']);
            },
            'user',
        ]);

        // フリーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('business_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('business_name_kana', 'LIKE', "%{$keyword}%")
                    ->orWhere('representative_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('representative_name_kana', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('classrooms', function ($cq) use ($keyword) {
                        $cq->where('classroom_name', 'LIKE', "%{$keyword}%")
                            ->orWhere('classroom_name_kana', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        // ステータス検索
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 登録の新しいものからソート
        $businesses = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // リクエストパラメータをビューに渡す（絞り込み条件の保持用）
        $filters = $request->only(['keyword', 'status']);

        return view('admin.business.index', compact('businesses', 'filters'));
    }

    /**
     * 事業者・教室・コースのCSVをZIPで出力（検索条件と同じクエリで全件）
     */
    public function exportCsv(Request $request, BusinessCsvExportService $exportService)
    {
        $query = BusinessInfo::with([
            'classrooms.courses',
            'classrooms.lessonCategoryInfo.parentCategory',
        ]);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('business_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('business_name_kana', 'LIKE', "%{$keyword}%")
                    ->orWhere('representative_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('representative_name_kana', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('classrooms', function ($cq) use ($keyword) {
                        $cq->where('classroom_name', 'LIKE', "%{$keyword}%")
                            ->orWhere('classroom_name_kana', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $businesses = $query->orderBy('id', 'asc')->get();

        $zipPath = $exportService->createZipPath($businesses);
        $zipFileName = basename($zipPath);

        return response()->streamDownload(function () use ($zipPath) {
            echo file_get_contents($zipPath);
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        }, $zipFileName, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.basename($zipPath).'"',
        ]);
    }

    /**
     * 事業者新規登録フォーム
     */
    public function create()
    {
        // フォームアクセス用トークンを生成してセッションに保存（郵便番号検索API用）
        $formToken = bin2hex(random_bytes(32));
        session(['form_access_token' => $formToken]);

        return view('admin.business.create', compact('formToken'));
    }

    /**
     * 事業者新規登録処理
     */
    public function store(StoreBusinessRequest $request)
    {
        $validated = $request->validated();
        $representativeNames = $this->resolveRepresentativeNames($validated);

        try {
            // subdomain_businessロールを取得
            $subdomainBusinessRole = Role::where('name', 'subdomain_business')->first();

            if (! $subdomainBusinessRole) {
                return back()->withErrors(['error' => 'subdomain_businessロールが見つかりません。'])->withInput();
            }

            // 現在のサブドメインIDを取得
            $subdomainService = new SubdomainService;
            $host = $request->getHost();
            $extractedSubdomain = $subdomainService->extractSubdomainFromHost($host);

            Log::info('Subdomain extraction debug', [
                'host' => $host,
                'extracted_subdomain' => $extractedSubdomain,
                'request_url' => $request->url(),
                'server_name' => $request->server('SERVER_NAME'),
                'http_host' => $request->server('HTTP_HOST'),
            ]);

            try {
                $currentSubdomain = $subdomainService->getCurrentSubdomain($request);

                Log::info('Subdomain found successfully', [
                    'subdomain_id' => $currentSubdomain->id,
                    'subdomain_name' => $currentSubdomain->subdomain,
                    'domain' => $currentSubdomain->domain,
                    'is_active' => $currentSubdomain->is_active,
                ]);

            } catch (\Exception $e) {
                Log::error('Subdomain not found', [
                    'host' => $host,
                    'extracted_subdomain' => $extractedSubdomain,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->withErrors(['error' => '現在のサブドメイン情報を取得できませんでした。ホスト: '.$host.', 抽出されたサブドメイン: '.$extractedSubdomain])->withInput();
            }

            // ユーザーアカウントを作成
            $user = User::create([
                'subdomain_id' => $currentSubdomain->id,
                'login_id' => $validated['login_id'],
                'password' => Hash::make($validated['password']),
                'name' => $representativeNames['representative_name'],
                'email' => $validated['email'],
                'role_id' => $subdomainBusinessRole->id,
                'is_active' => true,
            ]);

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'login_id' => $user->login_id,
                'subdomain_id' => $user->subdomain_id,
                'role_id' => $user->role_id,
                'name' => $user->name,
            ]);

            // 事業者情報を作成
            $businessData = $validated;
            $businessData['representative_name'] = $representativeNames['representative_name'];
            $businessData['representative_name_kana'] = $representativeNames['representative_name_kana'];
            $businessData['user_id'] = $user->id;
            $businessData['subdomain_id'] = $currentSubdomain->id;
            $businessData['created_user'] = Auth::id();

            // パスワード関連の項目を除外
            unset($businessData['login_id'], $businessData['password'], $businessData['password_confirmation']);

            // 承認フィールドの処理（チェックが外れている場合は0を設定）
            if (isset($validated['apply']) === false) {
                $businessData['apply'] = 0;
            }

            Log::info('About to create business with data', [
                'business_data_keys' => array_keys($businessData),
                'subdomain_id_value' => $businessData['subdomain_id'],
                'user_id_value' => $businessData['user_id'],
                'business_name' => $businessData['business_name'],
            ]);

            $business = BusinessInfo::create($businessData);

            // 作成後の実際のデータを確認
            $createdBusiness = BusinessInfo::find($business->id);

            Log::info('Business created with user account', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'subdomain_id' => $currentSubdomain->id,
                'subdomain_name' => $currentSubdomain->subdomain,
                'host' => $request->getHost(),
                'login_id' => $validated['login_id'],
                'actual_business_subdomain_id' => $createdBusiness->subdomain_id,
                'business_fillable' => $business->getFillable(),
            ]);

            return redirect()->route('admin.business.index')
                ->with('success', '事業者とログインアカウントを登録しました。');

        } catch (\Exception $e) {
            Log::error('Business creation failed', [
                'error' => $e->getMessage(),
                'login_id' => $validated['login_id'] ?? null,
                'host' => $request->getHost(),
            ]);

            return back()->withErrors(['error' => '事業者の登録中にエラーが発生しました。エラー: '.$e->getMessage()])->withInput();
        }
    }

    /**
     * 事業者編集フォーム
     */
    public function edit(BusinessInfo $business)
    {
        // フォームアクセス用トークンを生成してセッションに保存（郵便番号検索API用）
        $formToken = bin2hex(random_bytes(32));
        session(['form_access_token' => $formToken]);

        // 関連する教室、コース情報、ユーザー情報も取得
        $business->load(['classrooms.courses', 'user']);

        return view('admin.business.edit', compact('business', 'formToken'));
    }

    /**
     * 事業者更新処理
     */
    public function update(UpdateBusinessRequest $request, BusinessInfo $business)
    {
        $validated = $request->validated();
        $representativeNames = $this->resolveRepresentativeNames($validated);
        $validated['representative_name'] = $representativeNames['representative_name'];
        $validated['representative_name_kana'] = $representativeNames['representative_name_kana'];

        // 公金振替対象（チェック未送信時は false）
        $validated['is_public_funds_transfer_target'] = $request->boolean('is_public_funds_transfer_target');

        // 管理者用備考
        $validated['admin_remarks'] = $request->input('admin_remarks');

        // 管理者用添付: 削除指定を処理
        $currentAttachments = $business->admin_attachments ?? [];
        $removeKeys = $request->input('admin_attachment_remove', []);
        if (is_array($removeKeys) && count($removeKeys) > 0) {
            foreach ($removeKeys as $keyToRemove) {
                $keyToRemove = is_string($keyToRemove) ? trim($keyToRemove) : '';
                if ($keyToRemove === '') {
                    continue;
                }
                $this->deleteAdminAttachmentFromS3($keyToRemove);
                $currentAttachments = array_values(array_filter($currentAttachments, function ($a) use ($keyToRemove) {
                    return ($a['s3_key'] ?? '') !== $keyToRemove;
                }));
            }
        }

        // 新規アップロード: 最大5件（既存＋新規）
        $newFiles = $request->file('admin_attachments', []);
        if (! is_array($newFiles)) {
            $newFiles = [];
        }
        if (count($currentAttachments) + count($newFiles) > 5) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['admin_attachments' => '管理者用添付は合計5件までです。']);
        }

        foreach ($newFiles as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $meta = $this->uploadAdminAttachmentToS3($business, $file);
            $currentAttachments[] = $meta;
        }

        $validated['admin_attachments'] = array_values($currentAttachments);

        // ステータスに応じてis_activeとapplyを自動設定
        $approvedStatuses = ['審査②通過', '審査通過メール送信済', '利用中'];
        $status = $validated['status'] ?? $business->status ?? '未着手';

        if (in_array($status, $approvedStatuses, true)) {
            $validated['is_active'] = 1;
            $validated['apply'] = 1;
            if ($business->approved_at === null) {
                $validated['approved_at'] = now()->toDateString();
            }
        } else {
            $validated['is_active'] = 0;
            $validated['apply'] = 0;
        }

        $validated['updated_user'] = Auth::id();
        $business->update($validated);

        if ($business->user_id !== null) {
            $user = $business->user;
            if ($user !== null) {
                $user->update(['name' => $representativeNames['representative_name']]);
            }
        }

        return redirect()->route('admin.business.index')
            ->with('success', '事業者情報を更新しました。');
    }

    /**
     * 管理者用添付をS3にアップロードし、メタデータを返す
     *
     * @return array{s3_key: string, size: int, original_filename: string, mime_type: string}
     */
    private function uploadAdminAttachmentToS3(BusinessInfo $business, \Illuminate\Http\UploadedFile $file): array
    {
        $subdomainId = $business->subdomain_id;
        $businessId = $business->id;
        $originalName = $file->getClientOriginalName();
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/u', '_', $basename);
        if ($safeName === '') {
            $safeName = 'file';
        }
        $prefix = S3KeyPrefix::forSubdomain($subdomainId);
        $s3Key = sprintf(
            '%s/business_admin_attachments/%d/%s_%s.%s',
            $prefix,
            $businessId,
            Str::uuid()->toString(),
            $safeName,
            $extension ?: 'bin'
        );

        $content = $file->get();
        $this->putToS3WithEnvGuard($s3Key, $content);

        return [
            's3_key' => $s3Key,
            'size' => $file->getSize(),
            'original_filename' => $originalName,
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * 管理者用添付をS3から削除
     */
    private function deleteAdminAttachmentFromS3(string $s3Key): void
    {
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

        try {
            if (Storage::disk('s3')->exists($s3Key)) {
                Storage::disk('s3')->delete($s3Key);
            }
        } catch (\Exception $e) {
            Log::warning('S3 admin attachment delete failed', ['s3_key' => $s3Key, 'error' => $e->getMessage()]);
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
    }

    /**
     * S3にput（環境変数ガード付き）
     */
    private function putToS3WithEnvGuard(string $key, string $content): void
    {
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

        try {
            Storage::disk('s3')->put($key, $content);
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
    }

    /**
     * 事業者無効化処理
     */
    public function deactivate(BusinessInfo $business)
    {
        $business->deactivate();

        return redirect()->route('admin.business.index')
            ->with('success', '事業者を無効化しました。関連する教室、コース、ユーザーアカウントも無効化されました。');
    }

    /**
     * 事業者有効化処理
     */
    public function activate(BusinessInfo $business)
    {
        $business->update(['is_active' => 1]);

        return redirect()->route('admin.business.index')
            ->with('success', '事業者を有効化しました。');
    }

    /**
     * 教室一覧
     */
    public function classrooms(BusinessInfo $business)
    {
        $classrooms = $business->classrooms()->with('courses')->get();

        return view('admin.business.classrooms', compact('business', 'classrooms'));
    }

    /**
     * 教室新規登録フォーム
     */
    public function createClassroom(BusinessInfo $business)
    {
        $classroom = null; // 新規作成時はnull

        // 習い事種別データを取得（ログインユーザーのサブドメインのもの）
        $user = Auth::user();
        $parentCategories = \App\Models\CourseParentCategory::with(['activeCategories'])
            ->forSubdomain($user->subdomain_id)
            ->active()
            ->ordered()
            ->get();

        // サブドメインの座標を取得（地図表示用）
        $subdomain = Subdomain::find($user->subdomain_id);
        $latitude = $subdomain->latitude ?? 35.6812;
        $longitude = $subdomain->longitude ?? 139.7671;

        return view('admin.business.classroom-form', compact('business', 'classroom', 'parentCategories', 'latitude', 'longitude'));
    }

    /**
     * 教室新規登録処理
     */
    public function storeClassroom(Request $request, BusinessInfo $business)
    {
        $validated = $request->validate([
            'classroom_name' => 'required|string|max:100',
            'classroom_name_kana' => 'nullable|string|max:100',
            'classroom_representative_name' => 'nullable|string|max:50',
            'classroom_representative_name_kana' => 'nullable|string|max:50',
            'classroom_postal_code' => 'nullable|string|max:10',
            'classroom_prefecture' => 'nullable|string|max:20',
            'classroom_city' => 'nullable|string|max:50',
            'classroom_address1' => 'nullable|string|max:100',
            'classroom_building_name' => 'nullable|string|max:100',
            'classroom_latitude' => 'nullable|numeric|min:-90|max:90',
            'classroom_longitude' => 'nullable|numeric|min:-180|max:180',
            'use_map' => 'nullable|boolean',
            'classroom_phone' => 'required|string|max:20',
            'classroom_fax' => 'nullable|string|max:20',
            'classroom_email' => 'nullable|email|max:255',
            'business_hours' => 'nullable|string|max:200',
            'holiday' => 'nullable|string|max:100',
            'classroom_introduction' => 'nullable|string',
            'service_type' => 'nullable|in:教室型,訪問型,通信型',
            'lesson_category' => 'required|integer',
            'lesson_category_other' => 'nullable|string|max:255|required_if:lesson_category,-1',
            'classroom_image' => 'nullable|image|mimes:jpeg,png|max:10240', // 10MB
            'apply' => 'nullable|boolean',
            'disallow_amount_specified_usage' => 'nullable|boolean',
            'qr_only' => 'nullable|boolean',
        ], [
            'classroom_postal_code.required' => '郵便番号を入力してください。',
            'classroom_prefecture.required' => '都道府県を選択してください。',
            'classroom_city.required' => '市区町村を入力してください。',
            'classroom_address1.required' => '住所を入力してください。',
            'service_type.required' => '習い事種別を選択してください。',
            'lesson_category.required' => '習い事種別を選択してください。',
            'lesson_category_other.required_if' => '習い事種別を選択してください。',
            'classroom_image.image' => '教室画像は画像ファイルをアップロードしてください。',
            'classroom_image.mimes' => '教室画像はJPEG、PNG形式のファイルをアップロードしてください。',
            'classroom_image.max' => '教室画像は10MB以下のファイルをアップロードしてください。',
        ]);

        $validated['business_info_id'] = $business->id;
        $validated['created_user'] = Auth::id();

        // 承認フィールドの処理（チェックが外れている場合は0を設定）
        if (isset($validated['apply']) === false) {
            $validated['apply'] = 0;
        }

        if (isset($validated['disallow_amount_specified_usage']) === false) {
            $validated['disallow_amount_specified_usage'] = false;
        }
        if (isset($validated['qr_only']) === false) {
            $validated['qr_only'] = false;
        }

        $useMap = filter_var($validated['use_map'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if (! $useMap) {
            $validated['classroom_latitude'] = null;
            $validated['classroom_longitude'] = null;
        }
        $validated['use_map'] = $useMap;

        $classroom = ClassroomInfo::create($validated);

        // 教室画像の処理
        if ($request->hasFile('classroom_image')) {
            try {
                $imageData = $this->imageProcessingService->processClassroomImage(
                    $request->file('classroom_image'),
                    $classroom->id,
                    'classrooms',
                    $business->subdomain_id
                );

                $classroom->update($imageData);

                Log::info('教室画像アップロード成功', [
                    'classroom_id' => $classroom->id,
                    'filename' => $imageData['classroom_image_original_filename'],
                ]);
            } catch (\Exception $e) {
                Log::error('教室画像アップロード失敗', [
                    'classroom_id' => $classroom->id,
                    'error' => $e->getMessage(),
                ]);

                // AJAXリクエストの場合はJSONレスポンスを返す
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '画像のアップロードに失敗しました: '.$e->getMessage(),
                    ], 422);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', '画像のアップロードに失敗しました: '.$e->getMessage());
            }
        }

        // AJAXリクエストの場合はJSONレスポンスを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '教室を登録しました。',
                'redirect' => route('admin.business.edit', $business),
            ]);
        }

        return redirect()->route('admin.business.edit', $business)
            ->with('success', '教室を登録しました。');
    }

    /**
     * 教室編集フォーム
     */
    public function editClassroom(BusinessInfo $business, ClassroomInfo $classroom)
    {
        // 最新のデータを取得するため、モデルをリフレッシュ
        $classroom->refresh();

        // 関連するコース情報も取得
        $classroom->load('courses');

        // 習い事種別データを取得（ログインユーザーのサブドメインのもの）
        $user = Auth::user();
        $parentCategories = \App\Models\CourseParentCategory::with(['activeCategories'])
            ->forSubdomain($user->subdomain_id)
            ->active()
            ->ordered()
            ->get();

        // サブドメインの座標を取得（地図表示用）
        $subdomain = Subdomain::find($user->subdomain_id);
        $latitude = $subdomain->latitude ?? 35.6812;
        $longitude = $subdomain->longitude ?? 139.7671;

        return view('admin.business.classroom-form', compact('business', 'classroom', 'parentCategories', 'latitude', 'longitude'));
    }

    /**
     * 教室更新処理
     */
    public function updateClassroom(Request $request, BusinessInfo $business, ClassroomInfo $classroom)
    {
        $validated = $request->validate([
            'classroom_name' => 'required|string|max:100',
            'classroom_name_kana' => 'required|string|max:100',
            'classroom_representative_name' => 'required|string|max:50',
            'classroom_representative_name_kana' => 'required|string|max:50',
            'classroom_postal_code' => 'required|string|max:10',
            'classroom_prefecture' => 'required|string|max:20',
            'classroom_city' => 'required|string|max:50',
            'classroom_address1' => 'required|string|max:100',
            'classroom_building_name' => 'nullable|string|max:100',
            'classroom_latitude' => 'nullable|numeric|min:-90|max:90',
            'classroom_longitude' => 'nullable|numeric|min:-180|max:180',
            'use_map' => 'nullable|boolean',
            'classroom_phone' => 'required|string|max:20',
            'classroom_fax' => 'nullable|string|max:20',
            'classroom_email' => 'nullable|email|max:255',
            'business_hours' => 'required|string|max:200',
            'holiday' => 'required|string|max:100',
            'classroom_introduction' => 'nullable|string',
            'service_type' => 'required|in:教室型,訪問型,通信型',
            'lesson_category' => 'required|integer',
            'lesson_category_other' => 'nullable|string|max:255|required_if:lesson_category,-1',
            'is_active' => 'nullable|boolean',
            'apply' => 'nullable|boolean',
            'disallow_amount_specified_usage' => 'nullable|boolean',
            'qr_only' => 'nullable|boolean',
            'classroom_image' => 'nullable|image|mimes:jpeg,png|max:10240', // 10MB
            'delete_classroom_image' => 'nullable|boolean',
        ]);

        // 教室画像削除の処理
        if ($request->has('delete_classroom_image')) {
            try {
                $this->imageProcessingService->deleteClassroomImage(
                    $classroom->classroom_image_s3_key,
                    $classroom->classroom_image_medium_s3_key,
                    $classroom->classroom_image_thumbnail_s3_key
                );

                // 画像削除後、データベースの画像フィールドをクリア
                $imageDeleteData = [
                    'classroom_image_original_filename' => null,
                    'classroom_image_s3_key' => null,
                    'classroom_image_file_size' => null,
                    'classroom_image_mime_type' => null,
                    'classroom_image_medium_s3_key' => null,
                    'classroom_image_thumbnail_s3_key' => null,
                ];

                // 即座にデータベースを更新（リダイレクト前に反映させるため）
                $classroom->update($imageDeleteData);

                // $validatedにも反映
                $validated = array_merge($validated, $imageDeleteData);

                Log::info('教室画像削除成功', [
                    'classroom_id' => $classroom->id,
                ]);
            } catch (\Exception $e) {
                Log::error('教室画像削除失敗', [
                    'classroom_id' => $classroom->id,
                    'error' => $e->getMessage(),
                ]);

                // AJAXリクエストの場合はJSONレスポンスを返す
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '画像の削除に失敗しました: '.$e->getMessage(),
                    ], 422);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', '画像の削除に失敗しました: '.$e->getMessage());
            }
        }

        // 新しい教室画像の処理
        if ($request->hasFile('classroom_image')) {
            try {
                // 既存の画像があれば削除
                if ($classroom->hasClassroomImage()) {
                    $this->imageProcessingService->deleteClassroomImage(
                        $classroom->classroom_image_s3_key,
                        $classroom->classroom_image_medium_s3_key,
                        $classroom->classroom_image_thumbnail_s3_key
                    );
                }

                $imageData = $this->imageProcessingService->processClassroomImage(
                    $request->file('classroom_image'),
                    $classroom->id,
                    'classrooms',
                    $business->subdomain_id
                );

                $validated = array_merge($validated, $imageData);

                Log::info('教室画像アップロード成功', [
                    'classroom_id' => $classroom->id,
                    'filename' => $imageData['classroom_image_original_filename'],
                ]);
            } catch (\Exception $e) {
                Log::error('教室画像アップロード失敗', [
                    'classroom_id' => $classroom->id,
                    'error' => $e->getMessage(),
                ]);

                // AJAXリクエストの場合はJSONレスポンスを返す
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '画像のアップロードに失敗しました: '.$e->getMessage(),
                    ], 422);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', '画像のアップロードに失敗しました: '.$e->getMessage());
            }
        }

        // 教室状態の更新
        if (isset($validated['is_active']) === false) {
            $classroom->update(['is_active' => '0']);
        }

        // 承認フィールドの処理（チェックが外れている場合は0を設定）
        if (isset($validated['apply']) === false) {
            $validated['apply'] = 0;
        }

        if (isset($validated['disallow_amount_specified_usage']) === false) {
            $validated['disallow_amount_specified_usage'] = false;
        }
        if (isset($validated['qr_only']) === false) {
            $validated['qr_only'] = false;
        }

        $useMap = filter_var($validated['use_map'] ?? true, FILTER_VALIDATE_BOOLEAN);
        if (! $useMap) {
            $validated['classroom_latitude'] = null;
            $validated['classroom_longitude'] = null;
        }
        $validated['use_map'] = $useMap;

        $validated['updated_user'] = Auth::id();
        $classroom->update($validated);

        // データベースの変更を反映するため、モデルをリフレッシュ
        $classroom->refresh();

        // AJAXリクエストの場合はJSONレスポンスを返す
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '教室情報を更新しました。',
                'redirect' => route('admin.business.edit-classroom', [$business, $classroom]),
            ]);
        }

        return redirect()->route('admin.business.edit-classroom', [$business, $classroom])
            ->with('success', '教室情報を更新しました。');
    }

    /**
     * 教室無効化処理
     */
    public function deactivateClassroom(BusinessInfo $business, ClassroomInfo $classroom)
    {
        $classroom->deactivate();

        return redirect()->route('admin.business.edit', $business)
            ->with('success', '教室を無効化しました。関連するコースも無効化されました。');
    }

    /**
     * 教室有効化処理
     */
    public function activateClassroom(BusinessInfo $business, ClassroomInfo $classroom)
    {
        $classroom->update(['is_active' => 1]);

        return redirect()->route('admin.business.edit', $business)
            ->with('success', '教室を有効化しました。');
    }

    /**
     * コース一覧
     */
    public function courses(BusinessInfo $business, ClassroomInfo $classroom)
    {
        $courses = $classroom->courses()->get();

        return view('admin.business.courses', compact('business', 'classroom', 'courses'));
    }

    /**
     * コース新規登録フォーム
     */
    public function createCourse(BusinessInfo $business, ClassroomInfo $classroom)
    {
        $course = null; // 新規作成時はnull
        $user = User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? Subdomain::first();
        $grades = $subdomain ? $subdomain->getGrades() : [];

        return view('admin.business.course-form', compact('business', 'classroom', 'course', 'grades'));
    }

    /**
     * コース新規登録処理
     */
    public function storeCourse(Request $request, BusinessInfo $business, ClassroomInfo $classroom)
    {
        $user = User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? Subdomain::first();
        $availableGrades = $subdomain ? $subdomain->getGrades() : [];

        $validated = $request->validate([
            'course_name' => 'required|string|max:100',
            'course_description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'tax_type' => 'required|string',
            'open_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:open_date',
            'is_active' => 'boolean',
            'grades' => 'required|array|min:1',
            'grades.*' => 'required|string|in:'.implode(',', $availableGrades),
        ], [
            'grades.required' => '対象学年を1つ以上選択してください。',
            'grades.min' => '対象学年を1つ以上選択してください。',
            'grades.*.in' => '選択された学年が無効です。',
        ]);

        $validated['business_info_id'] = $business->id;
        $validated['classroom_info_id'] = $classroom->id;
        $validated['created_user'] = Auth::id();

        CourseInfo::create($validated);

        return redirect()->route('admin.business.edit-classroom', [$business, $classroom])
            ->with('success', 'コースを登録しました。');
    }

    /**
     * コース編集フォーム
     */
    public function editCourse(BusinessInfo $business, ClassroomInfo $classroom, CourseInfo $course)
    {
        $user = User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? Subdomain::first();
        $grades = $subdomain ? $subdomain->getGrades() : [];

        return view('admin.business.course-form', compact('business', 'classroom', 'course', 'grades'));
    }

    /**
     * コース更新処理
     */
    public function updateCourse(Request $request, BusinessInfo $business, ClassroomInfo $classroom, CourseInfo $course)
    {
        $user = User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? Subdomain::first();
        $availableGrades = $subdomain ? $subdomain->getGrades() : [];

        $validated = $request->validate([
            'course_name' => 'required|string|max:100',
            'course_description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'tax_type' => 'required|string',
            'open_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:open_date',
            'is_active' => 'boolean',
            'grades' => 'required|array|min:1',
            'grades.*' => 'required|string|in:'.implode(',', $availableGrades),
        ], [
            'grades.required' => '対象学年を1つ以上選択してください。',
            'grades.min' => '対象学年を1つ以上選択してください。',
            'grades.*.in' => '選択された学年が無効です。',
        ]);

        // コース状態の更新
        if (isset($validated['is_active']) === false) {
            $course->update(['is_active' => '0']);
        }

        $validated['updated_user'] = Auth::id();
        $course->update($validated);

        return redirect()->route('admin.business.edit-classroom', [$business, $classroom])
            ->with('success', 'コース情報を更新しました。');
    }

    /**
     * コース無効化処理
     */
    public function deactivateCourse(BusinessInfo $business, ClassroomInfo $classroom, CourseInfo $course)
    {
        $course->deactivate();

        return redirect()->route('admin.business.edit-classroom', [$business, $classroom])
            ->with('success', 'コースを無効化しました。');
    }

    /**
     * コース有効化処理
     */
    public function activateCourse(BusinessInfo $business, ClassroomInfo $classroom, CourseInfo $course)
    {
        $course->update(['is_active' => 1]);

        return redirect()->route('admin.business.edit-classroom', [$business, $classroom])
            ->with('success', 'コースを有効化しました。');
    }

    /**
     * ビジネス書類ダウンロード
     */
    public function downloadDocument(BusinessInfo $business, string $type)
    {
        // ユーザーの権限を確認（管理者、サブドメイン管理者、サブドメイン作業者のみ）
        $user = Auth::user();
        if (! $user || ! in_array($user->role->level, [100, 80, 60])) {
            abort(403, 'このファイルにアクセスする権限がありません。');
        }

        // 書類タイプの検証（PHP 8.3+ in_array with match alternative）
        $validTypes = BusinessInfo::getDocumentTypes();
        if (! in_array($type, $validTypes)) {
            abort(404, '指定された書類タイプが見つかりません。');
        }

        try {
            // ドキュメント情報を取得
            $docInfo = $business->getDocumentInfo($type);

            if (empty($docInfo['s3_key']) || empty($docInfo['original_filename'])) {
                abort(404, '書類ファイルが見つかりません。');
            }

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
                $fileExists = Storage::disk('s3')->exists($docInfo['s3_key']);
            } catch (\Exception $e) {
                Log::error('S3 existence check failed', [
                    's3_key' => $docInfo['s3_key'],
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
                $fileContent = Storage::disk('s3')->get($docInfo['s3_key']);
            } catch (\Exception $e) {
                Log::error('S3 file get failed', [
                    's3_key' => $docInfo['s3_key'],
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

            $mimeType = $docInfo['mime_type'] ?? 'application/octet-stream';

            // ログ記録
            Log::info('Document downloaded', [
                'business_id' => $business->id,
                'document_type' => $type,
                'user_id' => $user->id,
                'filename' => $docInfo['original_filename'],
                'file_size' => $docInfo['file_size'],
            ]);

            // ファイルをダウンロード（Laravel 12のレスポンス改善を活用）
            return response()->streamDownload(
                function () use ($fileContent) {
                    echo $fileContent;
                },
                $docInfo['original_filename'],
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ]
            );

        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Document download failed', [
                'business_id' => $business->id,
                'document_type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }

    /**
     * 管理者用添付ファイルダウンロード
     */
    public function downloadAdminAttachment(Request $request, BusinessInfo $business)
    {
        $key = $request->query('key');
        if (! is_string($key) || $key === '') {
            abort(404, '指定が不正です。');
        }

        $attachments = $business->admin_attachments ?? [];
        $found = null;
        foreach ($attachments as $att) {
            if (($att['s3_key'] ?? '') === $key) {
                $found = $att;
                break;
            }
        }
        if ($found === null) {
            abort(404, 'ファイルが見つかりません。');
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

        try {
            $fileExists = Storage::disk('s3')->exists($key);
        } catch (\Exception $e) {
            Log::error('S3 existence check failed (admin attachment)', ['key' => $key, 'error' => $e->getMessage()]);
            $fileExists = false;
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

        if (! $fileExists) {
            abort(404, 'ファイルが見つかりません。');
        }

        if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }

        try {
            $fileContent = Storage::disk('s3')->get($key);
        } catch (\Exception $e) {
            Log::error('S3 file get failed (admin attachment)', ['key' => $key, 'error' => $e->getMessage()]);
            abort(404, 'ファイルの取得に失敗しました。');
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

        $filename = $found['original_filename'] ?? 'download';
        $mimeType = $found['mime_type'] ?? 'application/octet-stream';

        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $filename,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    /**
     * 教室画像ダウンロード
     */
    public function downloadClassroomImage(BusinessInfo $business, ClassroomInfo $classroom, string $size)
    {
        // ユーザーの権限を確認（管理者、サブドメイン管理者、サブドメイン作業者のみ）
        $user = Auth::user();
        if (! $user || ! in_array($user->role->level, [100, 80, 60])) {
            abort(403, 'この画像にアクセスする権限がありません。');
        }

        // 教室が事業者に属しているかを確認
        if ($classroom->business_info_id !== $business->id) {
            abort(404, '指定された教室が見つかりません。');
        }

        // 画像が存在するかを確認
        if (! $classroom->hasClassroomImage()) {
            abort(404, '教室画像が見つかりません。');
        }

        // サイズの検証
        $validSizes = ['original', 'medium', 'thumbnail'];
        if (! in_array($size, $validSizes)) {
            abort(404, '指定された画像サイズが見つかりません。');
        }

        try {
            // S3キーを取得
            $s3Key = $classroom->getImageS3Key($size);

            if (empty($s3Key)) {
                abort(404, '画像ファイルが見つかりません。');
            }

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

            // S3からファイルを取得（存在確認で例外が発生する可能性があるため、try-catchで囲む）
            try {
                $fileExists = Storage::disk('s3')->exists($s3Key);
            } catch (\Exception $e) {
                Log::error('S3 existence check failed', [
                    's3_key' => $s3Key,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                    'app_env' => env('APP_ENV'),
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
                Log::warning('S3 file not found', [
                    's3_key' => $s3Key,
                    'classroom_id' => $classroom->id,
                    'size' => $size,
                ]);
                abort(404, 'ファイルが見つかりません。');
            }

            // ファイル取得時にも例外が発生する可能性があるため、try-catchで囲む
            try {
                // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
                if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                    unset($_ENV['AWS_ACCESS_KEY_ID']);
                    putenv('AWS_ACCESS_KEY_ID');
                }
                if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                    unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                    putenv('AWS_SECRET_ACCESS_KEY');
                }

                $fileContent = Storage::disk('s3')->get($s3Key);
            } catch (\Exception $e) {
                Log::error('S3 file get failed', [
                    's3_key' => $s3Key,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                    'app_env' => env('APP_ENV'),
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
            $mimeType = $classroom->classroom_image_mime_type ?? 'image/jpeg';

            // ファイル名を生成
            $extension = match ($mimeType) {
                'image/jpeg' => '.jpg',
                'image/png' => '.png',
                default => '.jpg'
            };

            $filename = $classroom->classroom_name.'_'.$size.$extension;

            // ログ記録
            Log::info('Classroom image downloaded', [
                'business_id' => $business->id,
                'classroom_id' => $classroom->id,
                'size' => $size,
                'user_id' => $user->id,
                'filename' => $filename,
            ]);

            // ファイルをダウンロード
            return response()->streamDownload(
                function () use ($fileContent) {
                    echo $fileContent;
                },
                $filename,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'private, max-age=3600',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Classroom image download failed', [
                'business_id' => $business->id,
                'classroom_id' => $classroom->id,
                'size' => $size,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, '画像のダウンロードに失敗しました。');
        }
    }

    /**
     * 事業者にログイン情報を送信
     */
    public function sendLoginInfo(Request $request, BusinessInfo $business): \Illuminate\Http\JsonResponse
    {
        try {
            // メールアドレスの確認
            if (empty($business->email)) {
                return response()->json([
                    'success' => false,
                    'message' => '事業者のメールアドレスが登録されていません。',
                ], 400);
            }

            // ステータスの確認（審査②通過、審査通過メール送信済、利用中の場合のみ送信可能）
            $allowedStatuses = ['審査②通過', '審査通過メール送信済', '利用中'];
            $currentStatus = $business->status ?? '未着手';
            if (! in_array($currentStatus, $allowedStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ステータスが「審査②通過」「審査通過メール送信済」「利用中」の場合のみログイン情報を送信できます。現在のステータス: '.$currentStatus,
                ], 400);
            }

            // ランダムパスワードを生成（英数字10文字）
            $password = Str::random(10);

            // サブドメイン事業者ロールを取得
            $subdomainBusinessRole = Role::where('name', 'subdomain_business')->first();

            if (! $subdomainBusinessRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'subdomain_businessロールが見つかりません。',
                ], 500);
            }

            if (empty($business->user_id)) {
                // ユーザーが存在しない場合は新規作成
                $user = User::create([
                    'subdomain_id' => $business->subdomain_id,
                    'login_id' => $business->email,
                    'password' => Hash::make($password),
                    'name' => $business->business_name,
                    'display_name' => $business->business_name,
                    'email' => $business->email,
                    'role_id' => $subdomainBusinessRole->id,
                    'is_active' => true,
                ]);

                // 事業者レコードにuser_idを登録
                $business->update(['user_id' => $user->id]);

                Log::info('事業者ログイン情報を新規作成', [
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'email' => $business->email,
                ]);
            } else {
                // 既存ユーザーのパスワードを更新
                $user = User::find($business->user_id);

                if (! $user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ユーザーが見つかりません。',
                    ], 404);
                }

                $user->update([
                    'password' => Hash::make($password),
                    'last_login_at' => null,
                ]);

                Log::info('事業者ログイン情報のパスワードを更新', [
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'email' => $business->email,
                ]);
            }

            // 現在のリクエストのドメインを使用してログインURLを生成
            $baseUrl = $request->getSchemeAndHttpHost();
            $loginUrl = $baseUrl.'/business/login';

            // メール送信内容を作成
            $subject = 'ログイン情報のお知らせ';

            if ($loginUrl) {
                $body = sprintf(
                    "%s 様\n\n".
                    "習い事クーポン管理システムのログイン情報をお知らせいたします。\n\n".
                    "ログインID: %s\n".
                    "パスワード: %s\n".
                    "ログインURL: %s\n\n".
                    "初回ログイン後、パスワードの変更をお願いいたします。\n".
                    "また、「コース管理」画面からコースの作成をお願いいたします。\n".
                    "（教室検索画面で、教室毎にコースが表示されます)\n\n".
                    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                    "このメールは自動送信されています。\n".
                    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
                    $business->business_name,
                    $business->email,
                    $password,
                    $loginUrl
                );
            } else {
                $body = sprintf(
                    "%s 様\n\n".
                    "習い事クーポン管理システムのログイン情報をお知らせいたします。\n\n".
                    "ログインID: %s\n".
                    "パスワード: %s\n\n".
                    "初回ログイン後、パスワードの変更をお願いいたします。\n\n".
                    "また、「コース管理」画面からコースの作成をお願いいたします。\n".
                    "（教室検索画面で、教室毎にコースが表示されます)\n\n".
                    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                    "このメールは自動送信されています。\n".
                    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n",
                    $business->business_name,
                    $business->email,
                    $password
                );
            }

            // メール送信
            Mail::raw($body, function ($message) use ($business, $subject) {
                $message->to($business->email)
                    ->from('info@www.study-and-experience.jp')
                    ->subject($subject);
            });

            // メール送信内容をログファイルに保存
            $this->mailLogService->logMail($business->email, $subject, $body);

            // ステータスが「審査通過」の場合は「審査通過メール送信済」に変更
            if ($currentStatus === '審査②通過') {
                $business->update(['status' => '審査通過メール送信済']);

                Log::info('事業者ステータスを自動更新', [
                    'business_id' => $business->id,
                    'old_status' => '審査②通過',
                    'new_status' => '審査通過メール送信済',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'ログイン情報をメールで送信しました。',
            ]);
        } catch (\Exception $e) {
            Log::error('ログイン情報送信エラー', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ログイン情報の送信中にエラーが発生しました。',
            ], 500);
        }
    }

    /**
     * 代表者の姓・名からフルネームを組み立てる（管理画面の新規登録・更新共通）
     *
     * @param  array<string, mixed>  $validated
     * @return array{representative_name: string, representative_name_kana: string}
     */
    private function resolveRepresentativeNames(array $validated): array
    {
        $representativeNameFull = trim((string) ($validated['representative_name'] ?? ''));
        if ($representativeNameFull === '') {
            $representativeNameFull = trim(
                (string) ($validated['representative_family_name'] ?? '').' '.(string) ($validated['representative_given_name'] ?? '')
            );
        }

        $representativeNameKanaFull = trim((string) ($validated['representative_name_kana'] ?? ''));
        if ($representativeNameKanaFull === '') {
            $representativeNameKanaFull = trim(
                (string) ($validated['representative_family_name_kana'] ?? '').' '.(string) ($validated['representative_given_name_kana'] ?? '')
            );
        }

        return [
            'representative_name' => $representativeNameFull,
            'representative_name_kana' => $representativeNameKanaFull,
        ];
    }
}
