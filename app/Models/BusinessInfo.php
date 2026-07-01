<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessInfo extends Model
{
    use HasFactory;

    /**
     * 申請者種別ごとの必須書類キーと表示ラベル・注意文（複数行可）
     *
     * @var array<string, array<string, array{label: string, notice: array<int, string>}>>
     */
    public const REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE = [
        'corporation' => [
            'service_and_fees' => ['label' => 'サービス内容及び費用が記載された文書', 'notice' => ['チラシ・パンフレット等']],
            'corporation_current_certificate' => ['label' => '法人の現在事項全部証明書', 'notice' => ['履歴事項全部証明書でも可／発行後３ヵ月以内']],
            'corporation_citizen_tax_certificate' => ['label' => '市税に係る徴収金（本税及び延滞金・督促手数料）を滞納していないことの証明', 'notice' => ['・申請時において所得できる直近の年度のもの', '・本市で事業を行っていないなどの理由により証明書がない場合は、申立書を添付してください。']],
            'bank_account_copy' => ['label' => '振込先の銀行等の通帳のコピー等', 'notice' => ['振込先の銀行等・支店・口座番号・名義人が全て記載されているページ。口座名義人は「法人名」又は「法人名＋法人代表者名」のものに限る。']],
        ],
        'voluntary_group' => [
            'service_and_fees' => ['label' => 'サービス内容及び費用が記載された文書', 'notice' => ['チラシ・パンフレット等']],
            'representative_id_copy' => ['label' => '団体代表者の公的身分証明書のコピー', 'notice' => ['運転免許証・パスポート・マイナンバーカード（マイナンバー部分は黒塗りしたもの）等の顔写真付きのものに限る。']],
            'group_rules' => ['label' => '団体の規約等', 'notice' => []],
            'group_officers_list' => ['label' => '団体の役員名簿', 'notice' => []],
            'representative_citizen_tax_certificate' => ['label' => '団体代表者の市税に係る徴収金（本税及び延滞金・督促手数料）を滞納していないことの証明', 'notice' => ['・申請時において所得できる直近の年度のもの', '・収益事業を行っていないなどの理由により証明書がない場合は、申立書を添付してください。']],
            'corporate_tax_certificate_no2' => ['label' => '直近の法人税納税証明書その２', 'notice' => ['ただし，事業開始後１事業年度未満等の理由で，法人税納税証明書その２の提出が困難な場合は，次の書類を提出', '・収益事業開始届出書の写し（所轄税務署の受付印のあるもの）※収益事業のない場合は不要', '・その他市がサービスの実態を確認できると認めた書類', '・収益事業を行っていないなどの理由により証明書がない場合は、申立書を添付してください。']],
            'bank_account_copy' => ['label' => '振込先の銀行等の通帳のコピー等', 'notice' => ['振込先の銀行等・支店・口座番号・名義人が全て記載されているページ。口座名義人は原則「団体名」又は「団体名＋団体代表者名」のものに限る']],
        ],
        'individual' => [
            'service_and_fees' => ['label' => 'サービス内容及び費用が記載された文書', 'notice' => ['チラシ・パンフレット等']],
            'id_copy' => ['label' => '公的身分証明書のコピー', 'notice' => ['運転免許証・パスポート・マイナンバーカード（マイナンバー部分は黒塗りしたもの）等の顔写真付きのものに限る。']],
            'citizen_tax_certificate' => ['label' => '市税に係る徴収金（本税及び延滞金・督促手数料）を滞納していないことの証明', 'notice' => ['・申請時において所得できる直近の年度のもの', '・市に納税義務がないなどの理由により証明書がない場合は、申立書を添付してください。']],
            'income_tax_return_copy' => ['label' => '直近の所得税確定申告書の写し（第一表と第二表（控）の写し）', 'notice' => ['納税手続をe-TAXで行っている場合：受付日時・受付番号が記載されているもの', '納税手続を税務署で行っている場合：所轄税務署の受付印のあるもの。', 'ただし，事業開始後１事業年度未満等の理由で，所得税確定申告書の写しの提出が困難な場合は，次の書類を提出', '・個人事業の開業・廃業等届出書の写し（所轄税務署の受付印のあるもの）', '・その他市がサービスの実態を確認できると認めた書類']],
            'bank_account_copy' => ['label' => '振込先の銀行等の通帳のコピー等', 'notice' => ['振込先の銀行等・支店・口座番号・名義人が全て記載されているページ。口座名義人は原則「団体名」又は「団体名＋団体代表者名」のものに限る']],
        ],
    ];

    protected $fillable = [
        'user_id',
        'subdomain_id',
        'applicant_type',
        'antisocial_forces_pledged',
        'privacy_policy_agreed',
        'business_name',
        'business_name_kana',
        'representative_title',
        'representative_family_name',
        'representative_given_name',
        'representative_name',
        'representative_title_kana',
        'representative_family_name_kana',
        'representative_given_name_kana',
        'representative_name_kana',
        'postal_code',
        'prefecture',
        'city',
        'address1',
        'building_name',
        'phone',
        'fax',
        'email',
        'website_url',
        'email_timing',
        'contact_person',
        'contact_phone',
        'document_person',
        'document_address',
        'business_hours',
        'holiday',
        'bank_code',
        'branch_code',
        'account_type',
        'account_number',
        'account_holder_name',
        'apply',
        'status',
        'approved_at',
        'is_active',
        'created_user',
        'updated_user',
        'documents',
        'qr_only',
        'is_public_funds_transfer_target',
        'admin_remarks',
        'admin_attachments',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'qr_only' => 'boolean',
            'is_public_funds_transfer_target' => 'boolean',
            'antisocial_forces_pledged' => 'boolean',
            'privacy_policy_agreed' => 'boolean',
            'approved_at' => 'date',
            'documents' => 'array',
            'admin_attachments' => 'array',
        ];
    }

    /**
     * 申請者種別に応じた必須書類キー一覧を取得
     *
     * @return array<int, string>
     */
    public static function getRequiredDocumentKeys(string $applicantType): array
    {
        $labels = self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE[$applicantType] ?? [];

        return array_keys($labels);
    }

    /**
     * 申請者種別と書類キーから表示ラベルを取得
     */
    public static function getDocumentLabel(string $applicantType, string $key): string
    {
        $item = self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE[$applicantType][$key] ?? null;

        return is_array($item) ? ($item['label'] ?? $key) : (string) $item;
    }

    /**
     * 申請者種別と書類キーから注意文（複数行）を取得
     *
     * @return array<int, string>
     */
    public static function getDocumentNotice(string $applicantType, string $key): array
    {
        $item = self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE[$applicantType][$key] ?? null;
        if (! is_array($item)) {
            return [];
        }
        $notice = $item['notice'] ?? [];
        if (is_string($notice)) {
            return $notice === '' ? [] : [$notice];
        }

        return array_values(array_filter($notice, fn ($line) => (string) $line !== ''));
    }

    /**
     * 申請者種別に応じた書類キーとラベル・注意文の一覧を取得（notice は常に配列）
     *
     * @return array<string, array{label: string, notice: array<int, string>}>
     */
    public static function getDocumentLabelsForApplicantType(string $applicantType): array
    {
        $items = self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE[$applicantType] ?? [];
        $result = [];
        foreach ($items as $key => $item) {
            $notice = $item['notice'] ?? [];
            $result[$key] = [
                'label' => $item['label'] ?? $key,
                'notice' => is_array($notice) ? array_values(array_filter($notice, fn ($line) => (string) $line !== '')) : ($notice === '' ? [] : [(string) $notice]),
            ];
        }

        return $result;
    }

    /**
     * アクティブな事業者のみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * 事業者に属する教室情報
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(ClassroomInfo::class);
    }

    /**
     * 事業者に属するコース情報
     */
    public function courses(): HasMany
    {
        return $this->hasMany(CourseInfo::class);
    }

    /**
     * 事業者を登録したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 事業者が属するサブドメイン
     */
    public function subdomain()
    {
        return $this->belongsTo(Subdomain::class);
    }

    /**
     * 事業者を作成したユーザー
     */
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user');
    }

    /**
     * 事業者を更新したユーザー
     */
    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user');
    }

    /**
     * 事業者を無効化し、関連する教室・コースも無効化
     */
    public function deactivate(): void
    {
        // 自身を無効化
        $this->update(['is_active' => 0]);

        // 関連する教室を無効化
        $this->classrooms()->update(['is_active' => 0]);

        // 関連するコースを無効化
        $this->courses()->update(['is_active' => 0]);
    }

    /**
     * 指定された書類キーの書類が存在するかチェック
     */
    public function hasDocument(string $documentKey): bool
    {
        $documents = $this->documents ?? [];
        $info = $documents[$documentKey] ?? null;

        return ! empty($info['s3_key']);
    }

    /**
     * 申請者種別に応じた必須書類がすべて揃っているかチェック
     */
    public function hasAllRequiredDocuments(): bool
    {
        $applicantType = $this->applicant_type ?? '';
        $keys = self::getRequiredDocumentKeys($applicantType);

        foreach ($keys as $key) {
            if (! $this->hasDocument($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 指定された書類キーの詳細情報を取得（管理画面表示・ダウンロード用）
     *
     * @return array{title: string, description: string, notice: array<int, string>, icon: string, color: string, s3_key: string|null, original_filename: string|null, file_size: int|null, mime_type: string|null}
     */
    public function getDocumentInfo(string $documentKey): array
    {
        $documents = $this->documents ?? [];
        $info = $documents[$documentKey] ?? [];
        $applicantType = $this->applicant_type ?? '';
        $title = self::getDocumentLabel($applicantType, $documentKey);
        $notice = self::getDocumentNotice($applicantType, $documentKey);

        return [
            'title' => $title,
            'description' => $title,
            'notice' => $notice,
            'icon' => 'fas fa-file-alt',
            'color' => 'blue',
            's3_key' => $info['s3_key'] ?? null,
            'original_filename' => $info['original_filename'] ?? null,
            'file_size' => $info['file_size'] ?? null,
            'mime_type' => $info['mime_type'] ?? null,
        ];
    }

    /**
     * 申請者種別に応じた書類キー一覧を取得（管理画面の書類一覧用）
     *
     * @return array<int, string>
     */
    public static function getDocumentTypes(?string $applicantType = null): array
    {
        if ($applicantType === null) {
            return array_values(array_unique(array_merge(
                array_keys(self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE['corporation'] ?? []),
                array_keys(self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE['voluntary_group'] ?? []),
                array_keys(self::REQUIRED_DOCUMENTS_BY_APPLICANT_TYPE['individual'] ?? [])
            )));
        }

        return self::getRequiredDocumentKeys($applicantType);
    }

    /**
     * 指定された書類キーのS3キーを取得
     */
    public function getDocumentS3Key(string $documentKey): ?string
    {
        $documents = $this->documents ?? [];
        $info = $documents[$documentKey] ?? null;

        return $info['s3_key'] ?? null;
    }

    /**
     * 指定された書類キーの元ファイル名を取得
     */
    public function getDocumentOriginalFilename(string $documentKey): ?string
    {
        $documents = $this->documents ?? [];
        $info = $documents[$documentKey] ?? null;

        return $info['original_filename'] ?? null;
    }

    /**
     * 利用可能なステータス一覧を取得
     */
    public static function getAvailableStatuses(): array
    {
        return [
            '未着手',
            '審査①開始',
            '審査①差し戻し',
            '審査①通過',
            '審査②開始',
            '審査②差し戻し',
            '審査②通過',
            '審査保留',
            '審査却下',
            '審査通過メール送信済',
            '利用中',
            '疑義あり調査中',
            '取り消し',
        ];
    }

    /**
     * ステータス表示用の色を取得
     */
    public function getStatusColor(): string
    {
        return match ($this->status ?? '未着手') {
            '未着手' => 'bg-gray-100 text-gray-800',
            // '審査中' => 'bg-blue-100 text-blue-800',
            // '審査差し戻し' => 'bg-yellow-100 text-yellow-800',
            '審査①開始' => 'bg-blue-100 text-blue-800',
            '審査①差し戻し' => 'bg-yellow-100 text-yellow-800',
            '審査①通過' => 'bg-green-100 text-green-800',
            '審査②開始' => 'bg-blue-100 text-blue-800',
            '審査②差し戻し' => 'bg-yellow-100 text-yellow-800',
            '審査②通過' => 'bg-green-100 text-green-800',
            '審査保留' => 'bg-yellow-100 text-yellow-800',
            '審査却下' => 'bg-red-100 text-red-800',
            '審査通過メール送信済' => 'bg-green-200 text-green-900',
            '利用中' => 'bg-purple-100 text-purple-800',
            '疑義あり調査中' => 'bg-orange-100 text-orange-800',
            '取り消し' => 'bg-gray-200 text-gray-900',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getRepresentativeNameFullAttribute(): string
    {
        $family = (string) ($this->representative_family_name ?? '');
        $given = (string) ($this->representative_given_name ?? '');

        if ($family !== '' || $given !== '') {
            return trim($family.'　'.$given);
        }

        return (string) ($this->representative_name ?? '');
    }

    public function getRepresentativeNameKanaFullAttribute(): string
    {
        $family = (string) ($this->representative_family_name_kana ?? '');
        $given = (string) ($this->representative_given_name_kana ?? '');

        if ($family !== '' || $given !== '') {
            return trim($family.'　'.$given);
        }

        return (string) ($this->representative_name_kana ?? '');
    }
}
