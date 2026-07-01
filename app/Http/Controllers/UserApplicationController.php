<?php

namespace App\Http\Controllers;

use App\Models\Subdomain;
use App\Models\UserApplication;
use App\Rules\RecaptchaV3;
use App\Services\S3KeyPrefix;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserApplicationController extends Controller
{
    use HandlesAuth;

    /**
     * 入力保持対象のフォーム項目キー
     *
     * @var array<int, string>
     */
    private const FORM_INPUT_KEYS = [
        'certification_number',
        'guardian_name_family',
        'guardian_name_given',
        'guardian_name_kana_family',
        'guardian_name_kana_given',
        'guardian_birth_date',
        'guardian_address',
        'guardian_phone',
        'guardian_email',
        'child_name_family',
        'child_name_given',
        'child_name_kana_family',
        'child_name_kana_given',
        'child_birth_date',
        'elementary_school_name',
        'grade',
        'child_address',
        'child_address_same_as_guardian',
        'child_registered_in_municipality_and_receiving_scholarship',
        'survey_consent',
        'privacy_policy_agreed',
        'classroom_name_1',
        'classroom_location_1',
        'classroom_phone_1',
        'classroom_contact_person_1',
        'classroom_name_2',
        'classroom_location_2',
        'classroom_phone_2',
        'classroom_contact_person_2',
        'classroom_name_3',
        'classroom_location_3',
        'classroom_phone_3',
        'classroom_contact_person_3',
    ];

    /**
     * 利用者申請フォーム表示
     */
    public function create(Request $request)
    {
        // フォームアクセス用トークンを生成してセッションに保存
        $formToken = bin2hex(random_bytes(32));
        session(['form_access_token' => $formToken]);

        // サブドメイン情報を取得
        $subdomain = $this->getCurrentSubdomain($request);

        $mode = 'create';
        $data = session('user_application.form_data', []);

        return view('user_application.form', compact('mode', 'data', 'formToken', 'subdomain'));
    }

    /**
     * 確認画面表示
     */
    public function confirm(Request $request)
    {
        $validator = $this->validateApplication($request);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            Log::error('Confirm validation failed', [
                'errors' => $errors,
                'input' => $this->getLoggableInputForFailedKeys($request, array_keys($errors)),
            ]);

            return back()->withErrors($validator)->withInput();
        }

        $data = $this->extractFormData($request);

        // ファイルアップロード情報を処理
        $this->handleFileUploadsForConfirm($request, $data);
        session(['user_application.form_data' => $data]);

        $mode = 'confirm';
        $subdomain = $this->getCurrentSubdomain($request);

        return view('user_application.form', compact('data', 'mode', 'subdomain'));
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

            return redirect()->route('user_application.create')
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

            // 申請者と同一の住所がチェックされている場合、child_addressにguardian_addressを設定
            $childAddress = $request->has('child_address_same_as_guardian') && $request->child_address_same_as_guardian === '1'
                ? $request->guardian_address
                : $request->child_address;

            $fullWidthSpace = '　';
            $guardianName = trim($request->guardian_name_family ?? '').$fullWidthSpace.trim($request->guardian_name_given ?? '');
            $guardianNameKana = trim($request->guardian_name_kana_family ?? '').$fullWidthSpace.trim($request->guardian_name_kana_given ?? '');
            $childName = trim($request->child_name_family ?? '').$fullWidthSpace.trim($request->child_name_given ?? '');
            $childNameKana = trim($request->child_name_kana_family ?? '').$fullWidthSpace.trim($request->child_name_kana_given ?? '');

            // 利用者申請情報を保存
            $userApplication = UserApplication::create([
                'subdomain_id' => $subdomain->id,
                'certification_number' => $request->certification_number,
                'guardian_name' => $guardianName,
                'guardian_name_kana' => $guardianNameKana,
                'guardian_birth_date' => $request->guardian_birth_date,
                'guardian_address' => $request->guardian_address,
                'guardian_phone' => $request->guardian_phone,
                'guardian_email' => $request->guardian_email,
                'child_name' => $childName,
                'child_name_kana' => $childNameKana,
                'child_birth_date' => $request->child_birth_date,
                'elementary_school_name' => $request->elementary_school_name,
                'grade' => $request->grade,
                'child_address' => $childAddress,
                'child_address_same_as_guardian' => $request->has('child_address_same_as_guardian'),
                'child_registered_in_municipality_and_receiving_scholarship' => $request->has('child_registered_in_municipality_and_receiving_scholarship'),
                'survey_consent' => $request->has('survey_consent'),
                'privacy_policy_agreed' => (bool) $request->privacy_policy_agreed,
                'classroom_name_1' => $request->classroom_name_1,
                'classroom_location_1' => $request->classroom_location_1,
                'classroom_phone_1' => $request->classroom_phone_1,
                'classroom_contact_person_1' => $request->classroom_contact_person_1,
                'classroom_name_2' => $request->classroom_name_2,
                'classroom_location_2' => $request->classroom_location_2,
                'classroom_phone_2' => $request->classroom_phone_2,
                'classroom_contact_person_2' => $request->classroom_contact_person_2,
                'classroom_name_3' => $request->classroom_name_3,
                'classroom_location_3' => $request->classroom_location_3,
                'classroom_phone_3' => $request->classroom_phone_3,
                'classroom_contact_person_3' => $request->classroom_contact_person_3,
                'is_exported' => false,
            ]);

            // ファイルをS3にアップロード
            $this->uploadDocumentToS3($userApplication, $subdomain->id);

            DB::commit();

            // セッションデータをクリア
            session()->forget('uploaded_files');
            session()->forget('user_application.form_data');

            return redirect()->route('user_application.complete')
                ->with('success', '利用者申請を受け付けました。');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('User application store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = '申請の処理中にエラーが発生しました。';

            return redirect()->route('user_application.create')
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    /**
     * 申請完了画面
     */
    public function complete()
    {
        return view('user_application.complete');
    }

    /**
     * バリデーションルール
     */
    private function validateApplication(Request $request)
    {
        // 申請者と同一の住所がチェックされているかどうか
        $childAddressSameAsGuardian = $request->has('child_address_same_as_guardian') && $request->child_address_same_as_guardian === '1';

        $rules = [
            // 必須項目
            'certification_number' => 'required|string|max:100',
            'guardian_name_family' => 'required|string|max:50',
            'guardian_name_given' => 'required|string|max:50',
            'guardian_name_kana_family' => 'required|string|max:50|regex:/^[ァ-ヶー]+$/u',
            'guardian_name_kana_given' => 'required|string|max:50|regex:/^[ァ-ヶー]+$/u',
            'guardian_birth_date' => 'required|date',
            'guardian_address' => 'required|string|max:500',
            'guardian_phone' => 'required|string|max:20|regex:/^.*\d{2,4}-\d{2,4}-\d{3,4}.*$/',
            'guardian_email' => 'required|email|max:255',
            'child_name_family' => 'required|string|max:50',
            'child_name_given' => 'required|string|max:50',
            'child_name_kana_family' => 'required|string|max:50|regex:/^[ァ-ヶー]+$/u',
            'child_name_kana_given' => 'required|string|max:50|regex:/^[ァ-ヶー]+$/u',
            'child_birth_date' => 'required|date',
            'elementary_school_name' => 'required|string|max:100',
            'grade' => 'required|string|max:50',
            'child_address' => $childAddressSameAsGuardian ? 'nullable|string|max:500' : 'required|string|max:500',
            'child_address_same_as_guardian' => 'nullable|boolean',
            'survey_consent' => 'required|accepted',
            'privacy_policy_agreed' => 'required|accepted',

            // 任意項目（教室情報、最大3つ）
            'classroom_name_1' => 'nullable|string|max:100',
            'classroom_location_1' => 'nullable|string|max:200',
            'classroom_phone_1' => 'nullable|string|max:20',
            'classroom_contact_person_1' => 'nullable|string|max:100',
            'classroom_name_2' => 'nullable|string|max:100',
            'classroom_location_2' => 'nullable|string|max:200',
            'classroom_phone_2' => 'nullable|string|max:20',
            'classroom_contact_person_2' => 'nullable|string|max:100',
            'classroom_name_3' => 'nullable|string|max:100',
            'classroom_location_3' => 'nullable|string|max:200',
            'classroom_phone_3' => 'nullable|string|max:20',
            'classroom_contact_person_3' => 'nullable|string|max:100',
        ];

        // reCAPTCHA: 確認画面へ進むとき（POST /user_application/confirm）のみ検証
        if ($request->routeIs('user_application.confirm') && config('recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['required', 'string', new RecaptchaV3];
        } else {
            $rules['g-recaptcha-response'] = ['nullable'];
        }

        // 添付ファイルのバリデーション（任意項目）
        $rules['tax_document'] = 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240'; // 10MB

        $messages = [
            'certification_number.required' => '就学援助認定番号を入力してください。',
            'certification_number.max' => '就学援助認定番号は100文字以内で入力してください。',
            'guardian_name_family.required' => '就学援助認定者名（保護者名）の姓を入力してください。',
            'guardian_name_family.max' => '就学援助認定者名（保護者名）の姓は50文字以内で入力してください。',
            'guardian_name_given.required' => '就学援助認定者名（保護者名）の名を入力してください。',
            'guardian_name_given.max' => '就学援助認定者名（保護者名）の名は50文字以内で入力してください。',
            'guardian_name_kana_family.required' => '就学援助認定者名カナ（保護者名）の姓を入力してください。',
            'guardian_name_kana_family.max' => '就学援助認定者名カナ（保護者名）の姓は50文字以内で入力してください。',
            'guardian_name_kana_family.regex' => '就学援助認定者名カナ（保護者名）の姓は全角カナで入力してください。',
            'guardian_name_kana_given.required' => '就学援助認定者名カナ（保護者名）の名を入力してください。',
            'guardian_name_kana_given.max' => '就学援助認定者名カナ（保護者名）の名は50文字以内で入力してください。',
            'guardian_name_kana_given.regex' => '就学援助認定者名カナ（保護者名）の名は全角カナで入力してください。',
            'guardian_birth_date.required' => '就学援助認定者生年月日を入力してください。',
            'guardian_birth_date.date' => '就学援助認定者生年月日は正しい日付形式で入力してください。',
            'guardian_address.required' => '住所を入力してください。',
            'guardian_address.max' => '住所は500文字以内で入力してください。',
            'guardian_phone.required' => '電話番号を入力してください。',
            'guardian_phone.regex' => '電話番号は正しい形式で入力してください。',
            'guardian_phone.max' => '電話番号は20文字以内で入力してください。',
            'guardian_email.required' => 'メールアドレスを入力してください。',
            'guardian_email.email' => '正しいメールアドレス形式で入力してください。',
            'guardian_email.max' => 'メールアドレスは255文字以内で入力してください。',
            'child_name_family.required' => '対象児童名の姓を入力してください。',
            'child_name_family.max' => '対象児童名の姓は50文字以内で入力してください。',
            'child_name_given.required' => '対象児童名の名を入力してください。',
            'child_name_given.max' => '対象児童名の名は50文字以内で入力してください。',
            'child_name_kana_family.required' => '対象児童名カナの姓を入力してください。',
            'child_name_kana_family.max' => '対象児童名カナの姓は50文字以内で入力してください。',
            'child_name_kana_family.regex' => '対象児童名カナの姓は全角カナで入力してください。',
            'child_name_kana_given.required' => '対象児童名カナの名を入力してください。',
            'child_name_kana_given.max' => '対象児童名カナの名は50文字以内で入力してください。',
            'child_name_kana_given.regex' => '対象児童名カナの名は全角カナで入力してください。',
            'child_birth_date.required' => '対象児童生年月日を入力してください。',
            'child_birth_date.date' => '対象児童生年月日は正しい日付形式で入力してください。',
            'elementary_school_name.required' => '小学校名を入力してください。',
            'elementary_school_name.max' => '小学校名は100文字以内で入力してください。',
            'grade.required' => '学年を入力してください。',
            'grade.max' => '学年は50文字以内で入力してください。',
            'child_address.required' => '対象児童の住所を入力してください。',
            'child_address.max' => '対象児童の住所は500文字以内で入力してください。',
            'survey_consent.required' => '調査同意にチェックを入れてください。',
            'survey_consent.accepted' => '調査同意にチェックを入れてください。',
            'privacy_policy_agreed.required' => 'プライバシーポリシーに同意のうえ、チェックしてください。',
            'privacy_policy_agreed.accepted' => 'プライバシーポリシーに同意してください。',

            // 教室情報
            'classroom_name_1.max' => '教室名1は100文字以内で入力してください。',
            'classroom_location_1.max' => '所在地1は200文字以内で入力してください。',
            'classroom_phone_1.max' => '電話番号1は20文字以内で入力してください。',
            'classroom_contact_person_1.max' => '担当者1は100文字以内で入力してください。',
            'classroom_name_2.max' => '教室名2は100文字以内で入力してください。',
            'classroom_location_2.max' => '所在地2は200文字以内で入力してください。',
            'classroom_phone_2.max' => '電話番号2は20文字以内で入力してください。',
            'classroom_contact_person_2.max' => '担当者2は100文字以内で入力してください。',
            'classroom_name_3.max' => '教室名3は100文字以内で入力してください。',
            'classroom_location_3.max' => '所在地3は200文字以内で入力してください。',
            'classroom_phone_3.max' => '電話番号3は20文字以内で入力してください。',
            'classroom_contact_person_3.max' => '担当者3は100文字以内で入力してください。',

            // 添付ファイル
            'tax_document.file' => '課税証明書等は有効なファイルをアップロードしてください。',
            'tax_document.mimes' => '課税証明書等はJPEG、PNG、PDF形式のファイルをアップロードしてください。',
            'tax_document.max' => '課税証明書等のファイルサイズが大きすぎます。10MB以下のファイルをアップロードしてください。',
            'g-recaptcha-response.required' => '確認に失敗しました。再度お試しください。',
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * 確認画面用のファイルアップロード処理
     */
    private function handleFileUploadsForConfirm(Request $request, array &$data): void
    {
        if ($request->hasFile('tax_document')) {
            $file = $request->file('tax_document');

            try {
                // セッションIDを使用して一時的に保存
                $sessionId = session()->getId();
                $tempPath = "temp/{$sessionId}/tax_document";

                // ファイル情報を事前に取得（move後は取得できないため）
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();

                // 一時ディレクトリを確実に作成
                $fullTempPath = storage_path('app/'.$tempPath);
                if (! is_dir($fullTempPath)) {
                    mkdir($fullTempPath, 0755, true);
                }

                // 直接move_uploaded_fileを使用
                $destinationPath = $fullTempPath.'/'.$originalName;

                if (move_uploaded_file($file->getPathname(), $destinationPath)) {
                    $path = $tempPath.'/'.$originalName;
                } else {
                    Log::error('move_uploaded_file failed', [
                        'source_path' => $file->getPathname(),
                        'destination_path' => $destinationPath,
                        'source_exists' => file_exists($file->getPathname()),
                        'destination_dir_writable' => is_writable(dirname($destinationPath)),
                    ]);

                    throw new \Exception('Failed to move uploaded file for tax_document');
                }

                // ファイルの存在確認
                $fullPath = storage_path('app/'.$path);

                if (! file_exists($fullPath)) {
                    throw new \Exception('File was not saved properly: '.$path);
                }

                // ファイル情報をデータに追加
                $data['tax_document'] = true;
                $data['tax_document_filename'] = $originalName;
                $data['tax_document_temp_path'] = $path;
                $data['tax_document_size'] = $fileSize;
                $data['tax_document_mime'] = $mimeType;

                // セッションにファイル情報を保存
                session(['uploaded_files.tax_document' => [
                    'original_name' => $originalName,
                    'temp_path' => $path,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                ]]);

            } catch (\Exception $e) {
                Log::error('Tax document upload failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * セッションからファイルをS3にアップロードして申請情報を更新
     */
    private function uploadDocumentToS3(UserApplication $userApplication, int $subdomainId): void
    {
        $uploadedFiles = session('uploaded_files', []);

        if (isset($uploadedFiles['tax_document'])) {
            $fileInfo = $uploadedFiles['tax_document'];

            // S3パスを生成
            $s3Path = $this->generateS3Path(
                $subdomainId,
                $userApplication->id,
                'tax_document',
                $fileInfo['original_name']
            );

            // S3にアップロード
            $this->uploadFileToS3($fileInfo['temp_path'], $s3Path);

            // 申請情報を更新
            $userApplication->update([
                'document_original_filename' => $fileInfo['original_name'],
                'document_s3_key' => $s3Path,
                'document_file_size' => $fileInfo['size'],
                'document_mime_type' => $fileInfo['mime_type'],
            ]);
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
    private function generateS3Path(int $subdomainId, int $applicationId, string $documentType, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $timestamp = now()->format('YmdHis');

        $prefix = S3KeyPrefix::forSubdomain($subdomainId);

        return "{$prefix}/user_applications/{$applicationId}/{$documentType}_{$timestamp}.{$extension}";
    }

    /**
     * バリデーションで失敗したキーに対応するリクエスト入力のみをログ用に抽出する
     *
     * @param  array<string>  $failedKeys
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
     * リクエストから入力保持対象のデータのみ抽出
     *
     * @return array<string, mixed>
     */
    private function extractFormData(Request $request): array
    {
        return Arr::only($request->all(), self::FORM_INPUT_KEYS);
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
}
