<?php

namespace App\Http\Controllers;

use App\Mail\BusinessApplicationReceivedMail;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\Subdomain;
use App\Rules\ClassroomImageValidation;
use App\Rules\RecaptchaV3;
use App\Services\BankService;
use App\Services\ImageProcessingService;
use App\Services\MailLogService;
use App\Services\S3KeyPrefix;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BusinessApplicationController extends Controller
{
    use HandlesAuth;

    protected BankService $bankService;

    public function __construct(
        BankService $bankService,
        protected MailLogService $mailLogService
    ) {
        $this->bankService = $bankService;
    }

    /**
     * 事業者申請フォーム表示
     */
    public function create(Request $request)
    {
        // フォームアクセス用トークンを生成してセッションに保存
        $formToken = bin2hex(random_bytes(32));
        session(['form_access_token' => $formToken]);

        // シンプルなカテゴリ一覧を取得
        $categories = collect();

        // サブドメイン情報を取得
        $subdomain = $this->getCurrentSubdomain($request);
        $latitude = $subdomain->latitude ?? null;
        $longitude = $subdomain->longitude ?? null;

        try {
            // 親カテゴリが存在するかチェック
            if (class_exists('App\Models\CourseParentCategory')) {
                $parentCategories = CourseParentCategory::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($parentCategories as $parent) {
                    $childCategories = CourseCategory::where('parent_category_id', $parent->id)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

                    if ($childCategories->count() > 0) {
                        $categories[$parent->id] = $childCategories;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load categories', ['error' => $e->getMessage()]);
            $categories = collect();
        }

        $mode = 'create';
        $data = [];

        $antisocialForcesText = '';
        $antisocialPath = resource_path('views/business_application/antisocial_forces.txt');
        if (file_exists($antisocialPath)) {
            $antisocialForcesText = file_get_contents($antisocialPath);
        }

        return view('business_application.form', compact('categories', 'mode', 'data', 'formToken', 'latitude', 'longitude', 'antisocialForcesText'));
    }

    /**
     * 確認画面表示
     */
    public function confirm(Request $request)
    {
        $validator = $this->validateApplication($request);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $failedKeys = array_keys($errors);
            Log::error('Confirm validation failed', [
                'errors' => $errors,
                'input' => $this->getLoggableInputForFailedKeys($request, $failedKeys),
            ]);

            // 書類アップロード関連のエラー時はサーバー設定をログに残す（原因切り分け用）
            $documentErrors = array_filter($failedKeys, fn (string $key) => str_starts_with($key, 'documents.'));
            if ($documentErrors !== []) {
                $fileUploadErrors = [];
                foreach ($request->file('documents', []) ?? [] as $docKey => $file) {
                    if ($file && method_exists($file, 'isValid') && ! $file->isValid()) {
                        $fileUploadErrors[$docKey] = [
                            'error_code' => $file->getError(),
                            'error_message' => $this->getUploadErrorMessage($file->getError()),
                        ];
                    }
                }
                Log::error('Document upload validation failed', [
                    'document_errors' => $documentErrors,
                    'file_upload_errors' => $fileUploadErrors,
                    'input' => $this->getLoggableInputForFailedKeys($request, $failedKeys),
                    'server_upload_limits' => [
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                        'post_max_size' => ini_get('post_max_size'),
                        'max_file_uploads' => ini_get('max_file_uploads'),
                        'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: '(default)',
                    ],
                    'note' => 'client_max_body_size is nginx config; check nginx.conf if post is truncated.',
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // カテゴリ情報も取得
            $categories = collect();
            try {
                if (class_exists('App\Models\CourseParentCategory')) {
                    $parentCategories = CourseParentCategory::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

                    foreach ($parentCategories as $parent) {
                        $childCategories = CourseCategory::where('parent_category_id', $parent->id)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get();

                        if ($childCategories->count() > 0) {
                            $categories[$parent->id] = $childCategories;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to load categories', ['error' => $e->getMessage()]);
                $categories = collect();
            }

            $mode = 'create';
            $data = $request->all();

            $antisocialForcesText = '';
            $antisocialPath = resource_path('views/business_application/antisocial_forces.txt');
            if (file_exists($antisocialPath)) {
                $antisocialForcesText = file_get_contents($antisocialPath);
            }

            return back()->withErrors($validator)->withInput()->with(compact('antisocialForcesText'));
        }

        $data = $request->all();

        // ファイルアップロード情報を処理（確認時点でS3一時キーに保存し、申請時にワーカー間でファイルが見つからない問題を防ぐ）
        $subdomain = $this->getCurrentSubdomain($request);
        $this->handleFileUploadsForConfirm($request, $data, $subdomain->id);

        // 教室画像のアップロード情報を処理
        $this->handleClassroomImagesForConfirm($request, $data, $subdomain->id);

        // カテゴリ情報を取得（表示用）
        if (! empty($data['classrooms'])) {
            foreach ($data['classrooms'] as $index => $classroom) {
                if (! empty($classroom['lesson_category'])) {
                    $category = CourseCategory::find($classroom['lesson_category']);
                    $data['classrooms'][$index]['lesson_category_name'] = $category ? $category->name : '';
                }
            }
        }

        // カテゴリ情報も必要
        $categories = collect();
        try {
            if (class_exists('App\Models\CourseParentCategory')) {
                $parentCategories = CourseParentCategory::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($parentCategories as $parent) {
                    $childCategories = CourseCategory::where('parent_category_id', $parent->id)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

                    if ($childCategories->count() > 0) {
                        $categories[$parent->id] = $childCategories;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load categories in confirm', ['error' => $e->getMessage()]);
            $categories = collect();
        }

        // 銀行・支店名の表示データを追加
        if (! empty($data['bank_code'])) {
            $data['bank_name_display'] = $this->bankService->getBankName($data['bank_code']);
        }
        if (! empty($data['bank_code']) && ! empty($data['branch_code'])) {
            $data['branch_name_display'] = $this->bankService->getBranchName($data['bank_code'], $data['branch_code']);
        }

        if ($request->wantsJson()) {
            $html = view('business_application.partials.confirm_section', compact('data', 'categories'))->render();

            return response()->json(['confirm_html' => $html]);
        }

        $mode = 'confirm';

        return view('business_application.form', compact('data', 'categories', 'mode'));
    }

    /**
     * 申請データの保存
     */
    public function store(Request $request)
    {
        $validator = $this->validateApplication($request);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            Log::error('Validation failed', [
                'errors' => $errors,
                'input' => $this->getLoggableInputForFailedKeys($request, array_keys($errors)),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return redirect()->route('business_application.create')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // サブドメインを取得（現在のサブドメインを使用）
            $host = request()->getHost();
            $subdomain = Subdomain::where('subdomain', $host)
                ->orWhere('subdomain', 'like', '%'.explode('.', $host)[0].'%')
                ->first() ?? Subdomain::first();

            $representativeNameFull = trim((string) $request->representative_family_name.' '.(string) $request->representative_given_name);
            $representativeNameKanaFull = trim((string) $request->representative_family_name_kana.' '.(string) $request->representative_given_name_kana);

            // 事業者情報を保存
            $businessInfo = BusinessInfo::create([
                'user_id' => null, // ログインなしなのでnull
                'subdomain_id' => $subdomain->id,
                'applicant_type' => $request->applicant_type,
                'antisocial_forces_pledged' => (bool) $request->antisocial_forces_pledged,
                'privacy_policy_agreed' => (bool) $request->privacy_policy_agreed,
                'business_name' => $request->business_name,
                'business_name_kana' => $request->business_name_kana,
                'representative_title' => $request->representative_title,
                'representative_family_name' => $request->representative_family_name,
                'representative_given_name' => $request->representative_given_name,
                'representative_name' => $representativeNameFull,
                'representative_title_kana' => $request->representative_title_kana,
                'representative_family_name_kana' => $request->representative_family_name_kana,
                'representative_given_name_kana' => $request->representative_given_name_kana,
                'representative_name_kana' => $representativeNameKanaFull,
                'postal_code' => $request->postal_code,
                'prefecture' => $request->prefecture,
                'city' => $request->city,
                'address1' => $request->address1,
                'building_name' => $request->building_name,
                'phone' => $request->phone,
                'fax' => $request->fax,
                'email' => $request->email,
                'website_url' => $request->website_url,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'document_person' => $request->document_person,
                'document_address' => $request->document_address,
                'business_hours' => $request->business_hours,
                'holiday' => $request->holiday,
                'bank_code' => $request->bank_code,
                'branch_code' => $request->branch_code,
                'account_type' => $request->account_type,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder,
                'apply' => 0, // 申請中
                'is_active' => 0,
            ]);

            // 教室情報を保存
            if ($request->has('classrooms')) {
                foreach ($request->classrooms as $index => $classroomData) {
                    $useMap = filter_var($classroomData['use_map'] ?? true, FILTER_VALIDATE_BOOLEAN);
                    $classroom = ClassroomInfo::create([
                        'business_info_id' => $businessInfo->id,
                        'classroom_name' => $classroomData['classroom_name'],
                        'classroom_name_kana' => $classroomData['classroom_name_kana'] ?? null,
                        'classroom_representative_name' => $classroomData['classroom_representative_name'] ?? null,
                        'classroom_representative_name_kana' => $classroomData['classroom_representative_name_kana'] ?? null,
                        'classroom_postal_code' => $classroomData['classroom_postal_code'] ?? null,
                        'classroom_prefecture' => $classroomData['classroom_prefecture'] ?? null,
                        'classroom_city' => $classroomData['classroom_city'] ?? null,
                        'classroom_address1' => $classroomData['classroom_address1'] ?? null,
                        'classroom_building_name' => $classroomData['classroom_building_name'] ?? null,
                        'classroom_latitude' => $useMap ? ($classroomData['classroom_latitude'] ?? null) : null,
                        'classroom_longitude' => $useMap ? ($classroomData['classroom_longitude'] ?? null) : null,
                        'use_map' => $useMap,
                        'classroom_phone' => $classroomData['classroom_phone'] ?? null,
                        'classroom_fax' => $classroomData['classroom_fax'] ?? null,
                        'classroom_email' => $classroomData['classroom_email'] ?? null,
                        'business_hours' => $classroomData['business_hours'] ?? null,
                        'holiday' => $classroomData['holiday'] ?? null,
                        'classroom_introduction' => $classroomData['classroom_introduction'] ?? null,
                        'service_type' => $classroomData['service_type'] ?? null,
                        'lesson_category' => $classroomData['lesson_category'] ?? null,
                        'lesson_category_other' => $classroomData['lesson_category_other'] ?? null,
                        'apply' => 0, // 申請中
                        'is_active' => 0,
                    ]);

                    // 教室画像のアップロード処理
                    $this->uploadClassroomImageToS3($classroom, $subdomain->id, $index);
                }
            }

            // ファイルをS3にアップロード
            $this->uploadDocumentsToS3($businessInfo, $subdomain->id);

            DB::commit();

            // セッションデータをクリア
            session()->forget(['uploaded_files', 'uploaded_classroom_images']);

            $contactUrl = url('/contact');
            $notifyEmail = (string) $businessInfo->email;
            if ($notifyEmail !== '' && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    $businessInfo->refresh();
                    $mailable = new BusinessApplicationReceivedMail($businessInfo, $subdomain, $contactUrl);
                    Mail::to($notifyEmail)->send($mailable);
                    $this->mailLogService->logMail(
                        $notifyEmail,
                        (string) $mailable->envelope()->subject,
                        $mailable->render()
                    );
                } catch (\Throwable $e) {
                    Log::error('事業者申請受付メール送信失敗', [
                        'business_info_id' => $businessInfo->id,
                        'email' => $notifyEmail,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            if ($request->wantsJson()) {
                return response()->json(['redirect_url' => route('business_application.complete')]);
            }

            return redirect()->route('business_application.complete')
                ->with('success', '事業者申請を受け付けました。');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Business application store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // エラーの種類に応じてメッセージを変更
            $errorMessage = '申請の処理中にエラーが発生しました。';

            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email')) {
                $errorMessage = '入力されたメールアドレスは既に登録されています。別のメールアドレスをご使用ください。';
            } elseif (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                $errorMessage = '入力されたデータに問題があります。入力内容を確認してください。';
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['general' => [$errorMessage]],
                ], 422);
            }

            return redirect()->route('business_application.create')
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * 申請完了画面
     */
    public function complete()
    {
        return view('business_application.complete');
    }

    /**
     * バリデーションエラーになった項目の入力値のみをログ用に整形する（ファイルはメタデータのみ）
     *
     * @param  array<int, string>  $failedKeys  エラーになったキー（例: ['business_name', 'documents.registration']）
     * @return array<string, mixed>
     */
    private function getLoggableInputForFailedKeys(Request $request, array $failedKeys): array
    {
        if ($failedKeys === []) {
            return [];
        }
        $all = $request->all();
        $result = [];
        foreach ($failedKeys as $key) {
            $value = Arr::get($all, $key);
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $result[$key] = [
                    'original_name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'error' => $value->getError(),
                    'mime_type' => $value->getMimeType(),
                ];
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeInputForLog($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 配列を再帰的に走査し、UploadedFile をメタデータに置き換える
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeInputForLog(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $result[$key] = [
                    'original_name' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'error' => $value->getError(),
                    'mime_type' => $value->getMimeType(),
                ];
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeInputForLog($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * PHP のアップロードエラーコードに対応する説明文（ログ用）
     */
    private function getUploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'upload_max_filesize exceeded',
            UPLOAD_ERR_FORM_SIZE => 'MAX_FILE_SIZE exceeded',
            UPLOAD_ERR_PARTIAL => 'file only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'no file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'failed to write to disk',
            UPLOAD_ERR_EXTENSION => 'extension stopped upload',
            default => 'unknown error ('.$code.')',
        };
    }

    /**
     * バリデーションルール
     */
    private function validateApplication(Request $request)
    {
        $rules = [
            // 事業者情報
            'applicant_type' => 'required|string|in:corporation,voluntary_group,individual',
            'antisocial_forces_pledged' => 'required|accepted',
            'privacy_policy_agreed' => 'required|accepted',
            'business_name' => 'required|string|max:100',
            'business_name_kana' => 'required|string|max:100|regex:/^[ァ-ヶー　]+$/u',
            'representative_title' => 'required|string|max:50',
            'representative_family_name' => 'required|string|max:50',
            'representative_given_name' => 'required|string|max:50',
            'representative_title_kana' => 'required|string|max:50|regex:/^[ァ-ヶー　]+$/u',
            'representative_family_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー　]+$/u',
            'representative_given_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー　]+$/u',
            'representative_name' => 'nullable|string|max:50',
            'representative_name_kana' => 'nullable|string|max:50|regex:/^[ァ-ヶー　]+$/u',
            'postal_code' => 'required|regex:/^\d{3}-\d{4}$/',
            'prefecture' => 'required|string|max:20',
            'city' => 'required|string|max:50',
            'address1' => 'required|string|max:100',
            'building_name' => 'nullable|string|max:100',
            'phone' => 'required|regex:/^0\d{1,4}-\d{1,4}-\d{3,4}$/',
            'fax' => 'nullable|regex:/^0\d{1,4}-\d{1,4}-\d{3,4}$/',
            'email' => ['required', 'email', 'max:255', Rule::unique('business_infos', 'email')],
            'website_url' => 'nullable|url|max:255',
            'contact_person' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|regex:/^0\d{1,4}-\d{1,4}-\d{3,4}$/',
            'document_person' => 'nullable|string|max:255',
            'document_address' => 'required|string|max:255',
            'business_hours' => 'nullable|string|max:200',
            'holiday' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:4',
            'branch_code' => 'nullable|string|max:3',
            'account_type' => 'nullable|string|max:10',
            'account_number' => 'nullable|string|max:7|min:7',
            'account_holder' => ['nullable', 'string', 'max:30', 'regex:/^[\x20\t0-9A-Z()\.\/,\x{FF61}-\x{FF9F}]*$/u'],

            // 教室情報（配列）
            'classrooms' => 'required|array|min:1|max:5',
            'classrooms.*.classroom_name' => 'required|string|max:100',
            'classrooms.*.classroom_name_kana' => 'nullable|string|max:100|regex:/^[ァ-ヶー　]+$/u',
            'classrooms.*.classroom_representative_name' => 'nullable|string|max:50',
            'classrooms.*.classroom_representative_name_kana' => 'nullable|string|max:50|regex:/^[ァ-ヶー　]+$/u',
            'classrooms.*.classroom_postal_code' => 'nullable|regex:/^\d{3}-\d{4}$/',
            'classrooms.*.classroom_prefecture' => 'nullable|string|max:20',
            'classrooms.*.classroom_city' => 'nullable|string|max:50',
            'classrooms.*.classroom_address1' => 'nullable|string|max:100',
            'classrooms.*.classroom_building_name' => 'nullable|string|max:100',
            'classrooms.*.classroom_fax' => 'nullable|regex:/^0\d{1,4}-\d{1,4}-\d{3,4}$/',
            'classrooms.*.classroom_email' => 'nullable|email|max:255',
            'classrooms.*.business_hours' => 'nullable|string|max:200',
            'classrooms.*.holiday' => 'nullable|string|max:100',
            'classrooms.*.classroom_introduction' => 'nullable|string',
            'classrooms.*.service_type' => 'nullable|string|max:50',
            'classrooms.*.lesson_category' => 'nullable|string|max:100',
            'classrooms.*.lesson_category_other' => 'nullable|string|max:255',
            'classrooms.*.use_map' => 'nullable|boolean',
            'classrooms.*.service_type' => 'required|string',
            'classrooms.*.lesson_category' => 'required|integer',

        ];

        // 地図を利用する場合（use_map が 0 でない）は緯度・経度を必須
        $classrooms = $request->input('classrooms', []);
        foreach (array_keys($classrooms) as $idx) {
            $rules["classrooms.{$idx}.classroom_latitude"] = ['required_unless:classrooms.'.$idx.'.use_map,0', 'nullable', 'numeric', 'min:-90', 'max:90'];
            $rules["classrooms.{$idx}.classroom_longitude"] = ['required_unless:classrooms.'.$idx.'.use_map,0', 'nullable', 'numeric', 'min:-180', 'max:180'];
        }

        // reCAPTCHA: 確認画面へ進むとき（POST /business_form/confirm）のみ検証
        if ($request->routeIs('business_application.confirm') && config('recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['required', 'string', new RecaptchaV3];
        } else {
            $rules['g-recaptcha-response'] = ['nullable'];
        }

        // 確認画面からの申請かどうか（書類キーで渡される documents_uploaded の有無で判定）
        $documentsUploaded = $request->input('documents_uploaded', []);
        $isFromConfirm = is_array($documentsUploaded) && count($documentsUploaded) > 0;

        // 教室画像のバリデーション（初回申請時のみ）
        if (! $isFromConfirm) {
            // 初回申請時（確認画面へ）のみ画像バリデーションを追加
            $rules['classrooms.*.classroom_image'] = ['nullable', new ClassroomImageValidation];
        }

        // 必須書類アップロード：申請者種別に応じたキーで検証
        $applicantType = $request->input('applicant_type', '');
        $requiredDocKeys = BusinessInfo::getRequiredDocumentKeys($applicantType);
        $maxSize = config('app.uploads.business_documents.max_size');
        $fileRule = 'required|file|mimes:jpeg,jpg,png,pdf,xls,xlsx,doc,docx|max:'.$maxSize;

        if (! $isFromConfirm) {
            foreach ($requiredDocKeys as $key) {
                $rules['documents.'.$key] = $fileRule;
            }
        } else {
            $uploadedFiles = session('uploaded_files', []);
            foreach ($requiredDocKeys as $key) {
                $hasUploaded = isset($documentsUploaded[$key]) && isset($uploadedFiles[$key]);
                if (! $hasUploaded) {
                    $rules['documents.'.$key] = 'required';
                }
            }
        }

        $messages = [
            'business_name_kana.regex' => '事業者名（カナ）は全角カナで入力してください。',
            'representative_title_kana.regex' => '代表者役職名（カナ）は全角カナで入力してください。',
            'representative_family_name_kana.regex' => '代表者名（カナ）（姓）は全角カナで入力してください。',
            'representative_given_name_kana.regex' => '代表者名（カナ）（名）は全角カナで入力してください。',
            'account_holder.regex' => '口座名義（カナ）は半角カナ・半角英大文字・数字・( ) . ｰ / , ・半角スペースのみ使用できます。',
            'account_holder.max' => '口座名義（カナ）は30文字以内で入力してください。',
            'postal_code.regex' => '郵便番号はxxx-xxxx形式で入力してください。',
            'phone.regex' => '電話番号は正しい形式で入力してください。',
            'email.unique' => '入力されたメールアドレスは既に登録されています。別のメールアドレスをご使用ください。',
            'contact_person.regex' => '連絡担当者は正しい形式で入力してください。',
            'fax.regex' => 'FAX番号は正しい形式で入力してください。',
            'website_url.url' => 'ウェブサイトURLは正しい形式で入力してください。',
            'classrooms.*.classroom_postal_code.regex' => '教室の郵便番号はxxx-xxxx形式で入力してください。',
            'classrooms.*.classroom_fax.regex' => '教室のFAX番号は正しい形式で入力してください。',
            'classrooms.*.classroom_latitude.required_unless' => '地図を利用する場合は地図をクリックして位置を指定してください。',
            'classrooms.*.classroom_longitude.required_unless' => '地図を利用する場合は地図をクリックして位置を指定してください。',
            'classrooms.max' => '教室は最大5件まで登録できます。',
            'account_number.max' => '口座番号は7桁で入力してください。',
            'account_number.min' => '口座番号は7桁で入力してください。',

            // 必須書類アップロード関連（書類キーごとのメッセージは下で動的追加）
            'documents.*.required' => '必須書類をすべてアップロードしてください。',
            'documents.*.uploaded' => 'ファイルのアップロードに失敗しました。ファイルサイズが大きすぎる、または接続が切れた可能性があります。10MB以下のファイルで再度お試しください。',
            'documents.*.mimes' => '書類の形式が正しくありません。JPEG、PNG、PDF、Excel、Wordファイルをアップロードしてください。',
            'documents.*.max' => '書類のファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。',

            // 教室画像関連
            'classrooms.*.classroom_name_kana.regex' => '教室名（カナ）は全角カナで入力してください。',
            'classrooms.*.classroom_representative_name_kana.regex' => '教室代表者名（カナ）は全角カナで入力してください。',
            'classrooms.*.classroom_image.image' => '教室画像は有効な画像ファイルをアップロードしてください。',
            'classrooms.*.classroom_image.mimes' => '教室画像はJPEG、PNG形式のファイルをアップロードしてください。',
            'classrooms.*.classroom_image.max' => '教室画像のファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。',
            'g-recaptcha-response.required' => '確認に失敗しました。再度お試しください。',
            'antisocial_forces_pledged.required' => '暴力団排除誓約事項をお読みいただき、チェックしてください。',
            'antisocial_forces_pledged.accepted' => '暴力団排除誓約事項に同意してください。',
            'privacy_policy_agreed.required' => 'プライバシーポリシーに同意のうえ、チェックしてください。',
            'privacy_policy_agreed.accepted' => 'プライバシーポリシーに同意してください。',
            'classrooms.*.service_type.required' => 'サービス提供の種類を選択してください。',
            'classrooms.*.lesson_category.required' => '習い事の種別を選択してください。',
        ];
        foreach ($requiredDocKeys as $key) {
            $label = BusinessInfo::getDocumentLabel($applicantType, $key);
            $messages['documents.'.$key.'.required'] = '必須書類「'.$label.'」をすべてアップロードしてください。';
            $messages['documents.'.$key.'.uploaded'] = '必須書類「'.$label.'」のアップロードに失敗しました。ファイルサイズが大きすぎる、または接続が切れた可能性があります。10MB以下のファイルで再度お試しください。';
            $messages['documents.'.$key.'.mimes'] = '必須書類「'.$label.'」はJPEG、PNG、PDF、Excel、Wordファイルをアップロードしてください。';
            $messages['documents.'.$key.'.max'] = '必須書類「'.$label.'」のファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。';
        }

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * セッションからファイルをS3にアップロードし、documents JSON でビジネス情報を更新
     */
    private function uploadDocumentsToS3(BusinessInfo $businessInfo, int $subdomainId): void
    {
        $uploadedFiles = session('uploaded_files', []);
        $documents = $businessInfo->documents ?? [];

        foreach ($uploadedFiles as $documentKey => $fileInfo) {
            $s3Path = $this->generateS3Path(
                $subdomainId,
                $businessInfo->id,
                $documentKey,
                $fileInfo['original_name']
            );

            $uploaded = false;
            if (! empty($fileInfo['s3_temp_key'])) {
                try {
                    $this->copyS3TempToFinalAndDelete($fileInfo['s3_temp_key'], $s3Path);
                    $uploaded = true;
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'S3 temporary object not found') && ! empty($fileInfo['temp_path'])) {
                        Log::warning('S3 temp not found, falling back to local temp file', [
                            'document_key' => $documentKey,
                            's3_temp_key' => $fileInfo['s3_temp_key'],
                        ]);
                    } else {
                        throw $e;
                    }
                }
            }
            if (! $uploaded && ! empty($fileInfo['temp_path'])) {
                $this->uploadFileToS3($fileInfo['temp_path'], $s3Path);
                $uploaded = true;
            }
            if (! $uploaded) {
                throw new \Exception("書類をアップロードできませんでした: {$documentKey}. S3一時オブジェクトが見つからず、ローカル一時ファイルもありません。");
            }

            $documents[$documentKey] = [
                's3_key' => $s3Path,
                'original_filename' => $fileInfo['original_name'],
                'file_size' => $fileInfo['size'],
                'mime_type' => $fileInfo['mime_type'],
            ];
        }

        $businessInfo->update(['documents' => $documents]);
        session()->forget('uploaded_files');
    }

    /**
     * 確認画面用のファイルアップロード処理（申請者種別ごとの書類キー対応）
     * ローカル一時保存とS3一時キーへの保存の両方を行う。申請時はS3を優先し、見つからなければローカルからアップロードする。
     */
    private function handleFileUploadsForConfirm(Request $request, array &$data, int $subdomainId): void
    {
        $documents = $request->file('documents');
        if (! is_array($documents)) {
            return;
        }

        $sessionId = session()->getId();
        $data['uploaded_documents'] = $data['uploaded_documents'] ?? [];

        foreach ($documents as $docKey => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            try {
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';

                // ローカル一時保存（申請時フォールバック用・同一ワーカーで参照可能）
                $tempPath = "temp/{$sessionId}/documents/{$docKey}";
                $fullTempPath = storage_path('app/'.$tempPath);
                if (! is_dir($fullTempPath)) {
                    mkdir($fullTempPath, 0755, true);
                }
                $destinationPath = $fullTempPath.'/'.$originalName;
                if (! move_uploaded_file($file->getPathname(), $destinationPath)) {
                    throw new \Exception('Failed to move uploaded file for '.$docKey);
                }
                $path = $tempPath.'/'.$originalName;

                // S3一時キーにも保存（別ワーカーでも参照可能にする試み）
                $prefix = S3KeyPrefix::forSubdomain($subdomainId);
                $s3TempKey = "{$prefix}/business_documents_temp/{$sessionId}/{$docKey}_".uniqid('', true).'.'.$extension;
                $fileContent = file_get_contents(storage_path('app/'.$path));
                if ($fileContent !== false) {
                    try {
                        $this->putToS3WithEnvGuard($s3TempKey, $fileContent);
                    } catch (\Throwable $e) {
                        Log::warning('S3 temp upload failed, will use local fallback on store', [
                            'document_key' => $docKey,
                            'error' => $e->getMessage(),
                        ]);
                        $s3TempKey = null;
                    }
                } else {
                    $s3TempKey = null;
                }

                $data['uploaded_documents'][$docKey] = [
                    'filename' => $originalName,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                ];

                $sessionPayload = [
                    'original_name' => $originalName,
                    'temp_path' => $path,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                ];
                if ($s3TempKey !== null) {
                    $sessionPayload['s3_temp_key'] = $s3TempKey;
                }
                session(['uploaded_files.'.$docKey => $sessionPayload]);
            } catch (\Exception $e) {
                Log::error('Document upload failed', [
                    'document_key' => $docKey,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * S3にputする際の環境変数ガード（AWS_* が "null" の場合の一時unset）
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
     * S3からオブジェクト内容を取得（環境変数ガード付き）
     */
    private function getFromS3WithEnvGuard(string $key): ?string
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
            $content = Storage::disk('s3')->get($key);

            return $content;
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
     * S3の一時オブジェクトを本番キーにコピーし、一時オブジェクトを削除する
     */
    private function copyS3TempToFinalAndDelete(string $s3TempKey, string $s3Path): void
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
            $disk = Storage::disk('s3');
            $content = $disk->get($s3TempKey);
            if ($content === null) {
                throw new \Exception("S3 temporary object not found: {$s3TempKey}");
            }
            $this->putToS3WithEnvGuard($s3Path, $content);
            $disk->delete($s3TempKey);
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
     * ファイルをS3にアップロード
     */
    private function uploadFileToS3(string $tempPath, string $s3Path): string
    {
        $fullPath = storage_path('app/'.$tempPath);

        if (! file_exists($fullPath)) {
            throw new \Exception("Temporary file not found: {$fullPath}");
        }

        $fileContent = file_get_contents($fullPath);

        if ($fileContent === false) {
            throw new \Exception("Failed to read temporary file: {$fullPath}");
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

        try {
            Storage::disk('s3')->put($s3Path, $fileContent);
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

        // 一時ファイルを削除
        unlink($fullPath);

        return $s3Path;
    }

    /**
     * S3パスを生成
     */
    private function generateS3Path(int $subdomainId, int $businessId, string $documentKey, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $timestamp = now()->format('YmdHis');

        $prefix = S3KeyPrefix::forSubdomain($subdomainId);

        return "{$prefix}/business_documents/{$businessId}/{$documentKey}_{$timestamp}.{$extension}";
    }

    /**
     * 確認画面用の教室画像アップロード処理（修正版）
     */
    private function handleClassroomImagesForConfirm(Request $request, array &$data, int $subdomainId): void
    {
        if (! isset($data['classrooms']) || ! is_array($data['classrooms'])) {
            return;
        }

        foreach ($data['classrooms'] as $index => &$classroomData) {
            if ($request->hasFile("classrooms.{$index}.classroom_image")) {
                $file = $request->file("classrooms.{$index}.classroom_image");

                try {
                    // セッションIDを使用して一時的に保存
                    $sessionId = session()->getId();
                    $tempPath = "temp/{$sessionId}/classroom_images";
                    $filename = "classroom_image_{$index}_{$file->getClientOriginalName()}";

                    // ファイル情報を事前に取得
                    $originalName = $file->getClientOriginalName();
                    $fileSize = $file->getSize();
                    $mimeType = $file->getMimeType();

                    // 一時ディレクトリを確実に作成
                    $fullTempPath = storage_path('app/'.$tempPath);
                    if (! is_dir($fullTempPath)) {
                        mkdir($fullTempPath, 0755, true);
                    }

                    // 直接move_uploaded_fileを使用
                    $destinationPath = $fullTempPath.'/'.$filename;

                    if (move_uploaded_file($file->getPathname(), $destinationPath)) {
                        $path = $tempPath.'/'.$filename;
                    } else {
                        throw new \Exception('Failed to move uploaded classroom image');
                    }

                    // ファイルの存在確認
                    $fullPath = storage_path('app/'.$path);
                    if (! file_exists($fullPath)) {
                        throw new \Exception('Classroom image was not saved properly: '.$path);
                    }

                    // S3一時キーにも保存（申請時・別ワーカーでも参照可能にする。書類アップロードと同じ方式）
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
                    $prefix = S3KeyPrefix::forSubdomain($subdomainId);
                    $s3TempKey = "{$prefix}/classroom_images_temp/{$sessionId}/{$index}_".uniqid('', true).'.'.$extension;
                    $fileContent = file_get_contents(storage_path('app/'.$path));
                    if ($fileContent !== false) {
                        try {
                            $this->putToS3WithEnvGuard($s3TempKey, $fileContent);
                        } catch (\Throwable $e) {
                            Log::warning('S3 temp upload failed for classroom image, will use local fallback on store', [
                                'index' => $index,
                                'error' => $e->getMessage(),
                            ]);
                            $s3TempKey = null;
                        }
                    } else {
                        $s3TempKey = null;
                    }

                    // ファイル情報をデータに追加
                    $classroomData['classroom_image'] = true;
                    $classroomData['classroom_image_filename'] = $originalName;
                    $classroomData['classroom_image_temp_path'] = $path;
                    $classroomData['classroom_image_size'] = $fileSize;
                    $classroomData['classroom_image_mime'] = $mimeType;

                    // セッションにファイル情報を保存（s3_temp_key があれば申請時にS3から取得して処理）
                    $sessionPayload = [
                        'original_name' => $originalName,
                        'temp_path' => $path,
                        'size' => $fileSize,
                        'mime_type' => $mimeType,
                    ];
                    if ($s3TempKey !== null) {
                        $sessionPayload['s3_temp_key'] = $s3TempKey;
                    }
                    session(["uploaded_classroom_images.{$index}" => $sessionPayload]);

                } catch (\Exception $e) {
                    Log::error('Classroom image upload failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }
    }

    /**
     * セッションから教室画像をS3にアップロード
     */
    private function uploadClassroomImageToS3(ClassroomInfo $classroom, int $subdomainId, int $index): void
    {
        $uploadedImages = session('uploaded_classroom_images', []);

        if (! isset($uploadedImages[$index])) {
            return;
        }

        $imageInfo = $uploadedImages[$index];

        try {
            $tempFilePath = null;
            $useS3Temp = ! empty($imageInfo['s3_temp_key']);

            if ($useS3Temp) {
                // S3一時から取得してローカル一時ファイルに書き出し（別ワーカーでも確実に処理可能）
                $content = $this->getFromS3WithEnvGuard($imageInfo['s3_temp_key']);
                if ($content === null) {
                    Log::warning('S3 temp classroom image not found, falling back to local', [
                        'index' => $index,
                        's3_temp_key' => $imageInfo['s3_temp_key'],
                    ]);
                    $useS3Temp = false;
                } else {
                    $sessionId = session()->getId();
                    $extension = pathinfo($imageInfo['original_name'], PATHINFO_EXTENSION) ?: 'bin';
                    $dlPath = "temp/{$sessionId}/classroom_dl";
                    $fullDlPath = storage_path('app/'.$dlPath);
                    if (! is_dir($fullDlPath)) {
                        mkdir($fullDlPath, 0755, true);
                    }
                    $tempFilePath = $fullDlPath.'/'.$index.'_'.uniqid('', true).'.'.$extension;
                    if (file_put_contents($tempFilePath, $content) === false) {
                        Log::error('Failed to write S3 classroom image to temp file', ['index' => $index]);
                        $useS3Temp = false;
                        $tempFilePath = null;
                    }
                }
            }

            if (! $useS3Temp) {
                $tempFilePath = storage_path('app/'.$imageInfo['temp_path']);
            }

            if ($tempFilePath === null || ! file_exists($tempFilePath)) {
                return;
            }

            // 画像処理サービスを使用
            $imageService = new ImageProcessingService;

            // UploadedFileオブジェクトを作成（test modeで作成）
            $tempFile = new \Illuminate\Http\UploadedFile(
                $tempFilePath,
                $imageInfo['original_name'],
                $imageInfo['mime_type'],
                null,
                true // test mode - ファイル存在チェックをスキップ
            );

            // 画像を処理してS3にアップロード
            $processedImage = $imageService->processClassroomImage(
                $tempFile,
                $classroom->id,
                'classrooms',
                $subdomainId
            );

            // 教室情報を更新
            $classroom->update([
                'classroom_image_original_filename' => $processedImage['classroom_image_original_filename'],
                'classroom_image_s3_key' => $processedImage['classroom_image_s3_key'],
                'classroom_image_file_size' => $processedImage['classroom_image_file_size'],
                'classroom_image_mime_type' => $processedImage['classroom_image_mime_type'],
                'classroom_image_medium_s3_key' => $processedImage['classroom_image_medium_s3_key'],
                'classroom_image_thumbnail_s3_key' => $processedImage['classroom_image_thumbnail_s3_key'],
            ]);

            // 一時ファイルを削除
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            // S3一時オブジェクトを削除
            if (! empty($imageInfo['s3_temp_key'])) {
                try {
                    Storage::disk('s3')->delete($imageInfo['s3_temp_key']);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete S3 temp classroom image', [
                        's3_temp_key' => $imageInfo['s3_temp_key'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Classroom image upload failed', [
                'classroom_id' => $classroom->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
